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
use Illuminate\Database\QueryException;
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
            $rest[$k]['created_at']   = $v['created_at'];
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

                $password   = $this->hashPassword($info['password'] ?? '');
                $randomName = Base::random(8, 'abcdefghijklmnopqrstuvwyz');
                # 准备用户数据
                $userData = [
                    'name'     => $info['name'] ?? $randomName,
                    'nickname' => $info['nickname'] ?? $randomName,
                    'mobile'   => $info['mobile'] ?? null,
                    'email'    => $info['email'],
                    'password' => $password
                ];
                # 创建用户
                $account = $this->accountModel->create($userData);
                # 分配角色
                foreach ($info['roles'] ?? [] as $roleId) {
                    $this->accountRoleModel->create([
                        'account_id' => $account->id,
                        'role_id'    => $roleId
                    ]);
                }
                return $account->id;
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $message = $e->getMessage();
                if (str_contains($message, 'email')) {
                    throw new CommonException('邮箱已注册');
                }
                if (str_contains($message, 'mobile')) {
                    throw new CommonException('手机号已存在');
                }
                if (str_contains($message, 'name')) {
                    throw new CommonException('账号名已存在');
                }
            }
            throw new CommonException('添加用户失败: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new CommonException('添加用户失败: ' . $e->getMessage());
        }
    }


    public function editStatus(int $status, int $id): bool
    {
        $one = $this->accountModel->find($id);
        if (!$one) {
            throw new CommonException('用户不存在');
        }
        $one->update(['status' => $status]);
        return true;
    }

    /**
     * 编辑用户信息
     */
    public function edit(array $info, int $id): bool
    {
        $one = $this->accountModel->find($id);
        if (!$one) {
            throw new CommonException('用户不存在');
        }
        if (array_key_exists('password', $info)) {
            $info['password'] = $this->hashPassword((string) $info['password']);
        }
        $one->update($info);
        return true;
    }

    /**
     * 更新密码。调用方可以传入明文或前端提交的 MD5 值，数据库始终保存 Laravel 哈希。
     */
    public function updatePassword(string $password, int $id): bool
    {
        return $this->edit(['password' => $password], $id);
    }

    /**
     * 编辑角色
     * @param array $info
     * @param int $id
     * @return bool
     */
    public function editRole(array $info, int $id): bool
    {
        DB::transaction(function () use ($info, $id) {
            $this->accountRoleModel->where('account_id', $id)->delete();
            foreach (array_unique(array_map('intval', $info['roles'] ?? [])) as $roleId) {
                $this->accountRoleModel->create([
                    'account_id' => $id,
                    'role_id'    => $roleId
                ]);
            }
        });
        return true;
    }


    public function del(int $id): void
    {
        # 删除用户
        $one = $this->accountModel->find($id);
        if (!$one) {
            throw new CommonException('用户不存在');
        }
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
        return $account ? $account->makeVisible('password')->toArray() : [];
    }

    public function getInfoByMobile(string $mobile): array
    {
        $account = $this->accountModel->where('mobile', $mobile)->first();
        return $account ? $account->makeVisible('password')->toArray() : [];
    }

    public function getInfoByEmail(string $email): array
    {
        $account = $this->accountModel->where('email', $email)->first();
        return $account ? $account->makeVisible('password')->toArray() : [];
    }

    public function getInfo(int $accountId): array
    {
        $account = $this->accountModel->where('id', $accountId)->first();
        return $account ? $account->toArray() : [];
    }

    public function findByField(string $field, string $value)
    {
        if (!in_array($field, ['id', 'name', 'nickname', 'mobile', 'email'], true)) {
            throw new CommonException('查询字段不合法');
        }
        $account = $this->accountModel->where($field, $value)->first();
        return $account ? $account->toArray() : [];
    }

    /**
     * 密码统一在后端哈希，兼容已哈希的注册数据。
     */
    private function hashPassword(string $password): string
    {
        if ($password === '') {
            $password = Base::random(16, 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%');
        }

        if (!empty(password_get_info($password)['algo'])) {
            return $password;
        }

        # 登录协议提交 MD5 摘要；兼容账号管理提交明文密码的旧前端。
        $password = preg_match('/^[a-f0-9]{32}$/i', $password) === 1
            ? strtolower($password)
            : md5($password);
        return Hash::make($password);
    }

    public function isSuperAdmin(int $accountId): bool
    {
        return $accountId == self::SUPPER_ADMIN_ID;
    }


}
