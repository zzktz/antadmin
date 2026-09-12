<?php
/**
 * 账号服务
 */

namespace Antmin\Http\Services;

use Exception;
use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\AccountRoleRepository;
use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\PermissionRepository;
use Antmin\Http\Repositories\TokenRepository;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AccountService
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountRepository     $accountRepo,
        protected RoleRepository        $roleRepo,
        protected PermissionRepository  $permissionRepo,
        protected TokenRepository       $tokenRepo,
        protected AccountRoleRepository $accountRoleRepo,
    )
    {
        # 依赖已通过容器自动注入
    }

    /**
     * 由 token 获取 accountId
     */
    public function getAccountIdByToken(string $token): int
    {
        try {
            return $this->tokenRepo->getIdByToken($token);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage(), [], -1, 401);
        }
    }

    /**
     * 账号基础信息
     */
    public function getAccountBaseInfo(int $accountId): array
    {
        $account = $this->accountRepo->getInfo($accountId);
        if (empty($account)) {
            throw new CommonException('用户信息不存在');
        }
        $roleName = $this->roleRepo->getRoleNameByAccountId($accountId);
        return [
            'id'       => $account['id'],
            'name'     => $account['name'],
            'username' => $account['nickname'],
            'roleName' => $roleName,
            'mobile'   => $account['mobile'],
            'email'    => $account['email'],
            'birthday' => $account['birthday'],
            'avatar'   => !empty($account['avatar']) ? $account['avatar'] : ''
        ];
    }

    /**
     * 账号列表
     */
    public function accountList(int $limit, int $accountId): array
    {
        # 权限验证
        $this->checkPermissions($accountId);

        return [
            'users' => $this->accountRepo->getFormatList($limit),
            'roles' => $this->roleRepo->getFormatAccountList(99),
            'rules' => $this->permissionRepo->getParentFormatToAccountList(99)
        ];
    }

    /**
     * 账号添加
     */
    public function accountAdd(array $info, int $accountId): int
    {

        $nickname = $info['nickname'];
        $email    = $info['email'];
        $mobile   = $info['mobile'];
        $roles    = $info['roles'] ?? [];
        $password = (string) ($info['password'] ?? '');

        # 权限验证
        $this->checkPermissions($accountId);
        # 密码强度验证
        if ($password !== '') {
            PasswordService::checkPasswordStrength($password);
        }

        # 参数验证
        if (empty($roles)) {
            throw new CommonException('角色值不存在');
        }
        # 角色权限检查
        if (in_array(1, $roles)) {
            throw new CommonException('超级管理员角色不可以添加');
        }

        # 生成唯一用户名
        $name = Base::random(8, 'abcdefghijkmnpqrstuvwxyz');

        # 唯一性检查
        if (!empty($this->accountRepo->getInfoByName($name))) {
            throw new CommonException('账号名已存在');
        }
        if (!empty($this->accountRepo->getInfoByMobile($mobile))) {
            throw new CommonException('手机号已存在');
        }
        if (!empty($this->accountRepo->getInfoByEmail($email))) {
            throw new CommonException('邮箱已存在');
        }


        $in['name']     = $name;
        $in['nickname'] = $nickname;
        $in['email']    = $email;
        $in['mobile']   = $mobile;
        $in['roles']    = $roles;
        $in['password'] = $password;

        # 添加用户
        return $this->accountRepo->add($in);
    }

    /**
     * 账号编辑
     */
    public function accountEdit(array $info, int $id, int $accountId): bool
    {
        # 权限验证
        $this->checkPermissions($accountId);

        $email  = $info['email'];
        $mobile = $info['mobile'];
        $roles  = $info['roles'] ?? [];

        # 唯一性检查
        $infoByMobile = $this->accountRepo->getInfoByMobile($mobile);
        if ($infoByMobile && $id != $infoByMobile['id']) {
            throw new CommonException('手机号已存在');
        }

        $infoByEmail = $this->accountRepo->getInfoByEmail($email);
        if ($infoByEmail && $id != $infoByEmail['id']) {
            throw new CommonException('邮箱已存在');
        }

        # 角色权限检查
        if (in_array(1, $roles)) {
            throw new CommonException('超级管理员角色不可以添加');
        }

        # 执行编辑并同步角色，两个变更必须在同一个事务中完成
        return DB::transaction(function () use ($info, $roles, $id) {
            unset($info['roles']);
            $result = $this->accountRepo->edit($info, $id);
            $this->accountRepo->editRole(['roles' => $roles], $id);
            return $result;
        });
    }

    /**
     * 个人编辑
     */
    public function personalEdit(string $field, string $value, int $accountId): bool
    {
        # 1. 字段白名单（根据业务需要，只允许更新哪些字段）
        $allowedFields = ['mobile', 'email', 'nickname'];
        if (!in_array($field, $allowedFields, true)) {
            throw new CommonException('不允许修改该字段');
        }

        # 2. 值不能为空（字符串长度为0，注意 '0' 是合法的）
        if ($value === '') {
            throw new CommonException('字段值不能为空');
        }

        # 3. 敏感词检查（昵称专用，其他字段不检查）
        if ($field === 'nickname') {
            $forbiddenWords = ['管理', 'admin', '官方', 'token', 'Token'];
            foreach ($forbiddenWords as $word) {
                if (str_contains($value, $word)) {
                    throw new CommonException('昵称不合法');
                }
            }
        }

        # 4. 唯一性检查（仅对需要唯一的字段）
        $uniqueFields = ['mobile', 'email', 'nickname'];
        if (in_array($field, $uniqueFields, true)) {
            $existing = $this->accountRepo->findByField($field, $value);
            if ($existing && ($existing['id'] ?? 0) !== $accountId) {
                $messages = [
                    'mobile'   => '手机号已存在',
                    'email'    => '邮箱已存在',
                    'nickname' => '昵称已存在'
                ];
                throw new CommonException($messages[$field]);
            }
        }

        # 5. 执行更新
        return $this->accountRepo->edit([$field => $value], $accountId);
    }

    /**
     * 更新状态
     */
    public function editStatus(int $id, int $accountId): bool
    {
        # 权限验证
        $this->checkPermissions($accountId);

        $account = $this->accountRepo->getInfo($id);
        if (empty($account)) {
            throw new CommonException('用户不存在');
        }

        $newStatus = empty($account['status']) ? 1 : 0;
        return $this->accountRepo->editStatus($newStatus, $id);
    }


    /**
     * 删除账号
     */
    public function accountDel(int $id, int $accountId): void
    {
        # 权限验证
        $this->checkPermissions($accountId);

        if ($this->accountRepo->isSuperAdmin($id)) {
            throw new CommonException('超级管理员不可以删除');
        }

        # 删除用户
        $this->accountRepo->del($id);
    }

    /**
     * 上传头像（改进版，使用 Laravel 的文件上传）
     */
    public function uploadAvatar(Request $request, int $accountId): string
    {
        # 验证上传的文件
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:1024'
        ]);

        # 生成存储路径
        $path = 'upload/avatar/' . date('Ymd');

        # 存储文件（使用 Laravel 的 Storage）
        $filePath = $request->file('avatar')->store($path, 'public');

        # 获取完整的 URL
        $imgUrl = Storage::disk('public')->url($filePath);

        # 更新用户头像
        $this->accountRepo->updateAvatar($filePath, $accountId);

        return $imgUrl;
    }


    /**
     * 重置默认密码
     * @param int $accountId
     * @return void
     */
    public function reInitPassword(int $accountId, int $operatorId): void
    {
        $this->checkPermissions($operatorId);
        if (empty($this->accountRepo->getInfo($accountId))) {
            throw new CommonException('用户不存在');
        }
        # 前端登录协议提交一次 MD5，重置密码也保存对应的协议值哈希。
        $defaultPassword = (string) config('antmin.default_password', '86662825');
        $this->accountRepo->updatePassword(md5($defaultPassword), $accountId);
    }


    private function checkPermissions(int $accountId): void
    {
        if (!$this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }


}
