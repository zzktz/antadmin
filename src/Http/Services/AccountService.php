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

class AccountService
{


    protected const DEF_PASSWORD = 'a@123456';


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

        return [
            'id'       => $account['id'],
            'name'     => $account['name'],
            'username' => $account['nickname'],
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
        $roles    = $info['roles'];
        $password = $info['password'];

        # 权限验证
        $this->checkPermissions($accountId);
        # 密码强度验证
        PasswordService::checkPasswordStrength($password);


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
        $roles  = $info['roles'];

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

        # 执行编辑
        return $this->accountRepo->edit($info, $id);
    }

    /**
     * 个人编辑
     */
    public function personalEdit(string $field, string $value, int $accountId): bool
    {
        if (empty($field) || empty($value)) {
            throw new CommonException('字段和值不能为空');
        }

        try {
            # 唯一性检查
            $existing = $this->accountRepo->findByField($field, $value);
            if (!empty($existing) && $accountId != $existing['id']) {
                $messages = [
                    'mobile' => '手机号已存在',
                    'email'  => '邮箱已存在',
                    'name'   => '账号名称已存在'
                ];

                if (isset($messages[$field])) {
                    throw new CommonException($messages[$field]);
                }
            }

            # 执行更新
            return $this->accountRepo->edit([$field => $value], $accountId);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
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
    public function reInitPassword(int $accountId)
    {
        $this->accountRepo->updatePassword(self::DEF_PASSWORD, $accountId);
    }


    private function checkPermissions(int $accountId): void
    {
        if (!$this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }


}
