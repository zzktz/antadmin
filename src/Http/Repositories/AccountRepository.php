<?php
/**
 * 账号服务
 */

namespace Antmin\Http\Repositories;

use DB;
use Exception;
use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Account as AccountModel;
use Antmin\Models\AccountRole as AccountRoleModel;
use Illuminate\Support\Facades\Hash;

class AccountRepository
{
    # 配置改为类常量
    protected const SUPPER_ADMIN_ID = 1;

    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountModel         $accountModel,
        protected AccountRoleModel     $accountRoleModel,
        protected RoleRepository       $roleRepository,
        protected PermissionRepository $permissionRepository
    )
    {
        # 依赖已通过容器自动注入
    }

    public function getInfoFormat(int $id): array
    {
        $one              = $this->getInfo($id);
        $info['id']       = $one['id'];
        $info['name']     = $one['name'];
        $info['username'] = $one['nickname'];
        $info['mobile']   = $one['mobile'];
        $info['email']    = $one['email'];
        $info['avatar']   = !empty($one['avatar']) ? Base::fillUrl($one['avatar']) : '';
        return $info;
    }

    public function getFormatList(int $limit): array
    {
        $datas = $this->getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }

        $rest = [];
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['name']         = $v['name'];
            $rest[$k]['username']     = $v['nickname'];
            $rest[$k]['mobile']       = $v['mobile'];
            $rest[$k]['email']        = $v['email'];
            $rest[$k]['birthday']     = $v['birthday'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['rolesData']    = $this->roleRepository->getRolesByAccountId($v['id'], ['id', 'name']);
            $rest[$k]['avatar']       = $v['avatar'] ? Base::fillUrl($v['avatar']) : '';
            $rest[$k]['roles']        = $this->roleRepository->getRolesIdsByAccountId($v['id']);
            $rest[$k]['rules']        = $this->permissionRepository->getAllPermissionsIdsByAccountId($v['id']);
            $rest[$k]['isShowDelete'] = $this->isSuperAdmin($v['id']) ? 0 : 1;
        }

        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest;
        return $res;
    }

    public function getList(int $limit): array
    {
        $query = $this->accountModel->newQuery();
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    /**
     * 添加用户
     */
    public function add(string $name, string $nickname, string $email, string $mobile, array $roles, string $password = ''): int
    {
        try {
            # 使用事务确保数据一致性
            return DB::transaction(function () use ($name, $nickname, $email, $mobile, $roles, $password) {
                # 准备用户数据
                $userData = [
                    'name'     => $name,
                    'nickname' => $nickname,
                    'mobile'   => $mobile,
                    'email'    => $email,
                    'password' => $password
                        ? Hash::make(md5($password))
                        : Hash::make(md5(str_random(12)))
                ];

                # 创建用户
                $account = $this->accountModel->create($userData);

                # 分配角色
                foreach ($roles as $roleId) {
                    $this->accountRoleModel->create([
                        'account_id' => $account->id,
                        'role_id'    => $roleId
                    ]);
                }

                return $account->id;
            });
        } catch (Exception $e) {
            throw new CommonException('添加用户失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新用户密码
     */
    public function updatePassword(string $password, int $accountId): bool
    {
        $encryptPassword = Hash::make(md5($password));
        return $this->accountModel->where('id', $accountId)->update(['password' => $encryptPassword]);
    }

    /**
     * 编辑用户信息
     */
    public function edit(string $nickname, string $email, string $mobile, array $roles, int $accountId): bool
    {
        try {
            return DB::transaction(function () use ($nickname, $email, $mobile, $roles, $accountId) {
                # 更新用户基本信息
                $userData = [
                    'nickname' => $nickname,
                    'mobile'   => $mobile,
                    'email'    => $email
                ];
                $this->accountModel->where('id', $accountId)->update($userData);

                # 更新用户角色
                $this->accountRoleModel->where('account_id', $accountId)->delete();
                foreach ($roles as $roleId) {
                    $this->accountRoleModel->create([
                        'account_id' => $accountId,
                        'role_id'    => $roleId
                    ]);
                }

                return true;
            });
        } catch (Exception $e) {
            throw new CommonException('编辑用户失败: ' . $e->getMessage());
        }
    }

    /**
     * 个人编辑
     */
    public function personalEdit(array $info, int $accountId): bool
    {
        try {
            return $this->accountModel->where('id', $accountId)->update($info);
        } catch (Exception $e) {
            throw new CommonException('更新个人信息失败: ' . $e->getMessage());
        }
    }

    public function del(int $accountId): bool
    {
        try {
            return DB::transaction(function () use ($accountId) {
                # 检查是否为超级管理员
                if ($this->isSuperAdmin($accountId)) {
                    throw new CommonException('不能删除超级管理员');
                }

                # 删除用户角色关联
                $this->accountRoleModel->where('account_id', $accountId)->delete();

                # 删除用户
                return $this->accountModel->where('id', $accountId)->delete() > 0;
            });
        } catch (Exception $e) {
            throw new CommonException('删除用户失败: ' . $e->getMessage());
        }
    }

    public function updateAvatar(string $avatar, int $accountId): bool
    {
        return $this->accountModel->where('id', $accountId)->update(['avatar' => $avatar]);
    }

    public function getInfoByName(string $name): array
    {
        $account = $this->accountModel->where('name', $name)->first();
        return $account ? $account->toArray() : [];
    }

    public function getInfoByMobile(string $mobile): array
    {
        $account = $this->accountModel->where('mobile', $mobile)->first();
        return $account ? $account->toArray() : [];
    }

    public function getInfoByEmail(string $email): array
    {
        $account = $this->accountModel->where('email', $email)->first();
        return $account ? $account->toArray() : [];
    }

    public function getInfo(int $accountId): array
    {
        $account = $this->accountModel->where('id', $accountId)->first();
        return $account ? $account->toArray() : [];
    }

    public function isSuperAdmin(int $accountId): bool
    {
        return $accountId == self::SUPPER_ADMIN_ID;
    }

    /**
     * 验证用户密码
     */
    public function verifyPassword(string $password, int $accountId): bool
    {
        $account = $this->accountModel->where('id', $accountId)->first();
        if (!$account) {
            return false;
        }
        return Hash::check(md5($password), $account->password);
    }


}
