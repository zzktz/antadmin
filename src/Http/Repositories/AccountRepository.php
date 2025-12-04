<?php
/**
 * 账号服务
 */

namespace Antmin\Http\Repositories;


use Exception;
use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Account as AccountModel;
use Antmin\Models\AccountRole as AccountRoleModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
    public function add(array $info): int
    {
        try {
            # 使用事务确保数据一致性
            return DB::transaction(function () use ($info) {
                # 准备用户数据
                $userData = [
                    'name'     => $info['name'],
                    'nickname' => $info['nickname'],
                    'mobile'   => $info['mobile'],
                    'email'    => $info['email'],
                    'password' => !empty($password) ? Hash::make(md5($password)) : Hash::make(md5(str_random(12)))
                ];
                # 创建用户
                $account = $this->accountModel->create($userData);
                # 分配角色
                foreach ($info['roles'] as $roleId) {
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


    public function editStatus(int $status, int $id): bool
    {
        $one = $this->accountModel->find($id);
        $one->update(['status' => $status]);
        return true;
    }

    /**
     * 编辑用户信息
     */
    public function edit(array $info, int $id): bool
    {
        $one = $this->accountModel->find($id);
        $one->update($info);

        # 删除所有
        $this->accountRoleModel->where('account_id', $id)->delete();
        $roles = $info['roles'] ?? [];
        if (empty($roles)) {
            return true;
        }
        # 重新添加
        foreach ($roles as $roleId) {
            $this->accountRoleModel->create([
                'account_id' => $id,
                'role_id'    => $roleId
            ]);
        }
        return true;
    }


    public function del(int $id): void
    {
        # 删除用户
        $one = $this->accountModel->find($id);
        $one->delete();
        # 删除用户角色关联
        $this->accountRoleModel->where('account_id', $id)->delete();
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


}
