<?php
/**
 * 账号
 */

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Account as Model;
use Antmin\Models\AccountRole;
use Exception;
use Illuminate\Support\Facades\Hash;

class AccountRepository extends Model
{


    # 定义超级管理员IDS
    protected static array $supperAdminIds = [1];

    protected static string $defaultPassword = '123456';

    public static function getInfoFormat(int $id): array
    {
        $one              = self::getInfo($id);
        $info['id']       = $one['id'];
        $info['name']     = $one['name'];
        $info['username'] = $one['nickname'];
        $info['mobile']   = $one['mobile'];
        $info['email']    = $one['email'];
        $info['avatar']   = !empty($one['avatar']) ? Base::fillUrl($one['avatar']) : '';
        return $info;
    }

    public static function getFormatList(int $limit): array
    {
        $datas = self::getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['name']         = $v['name'];
            $rest[$k]['username']     = $v['nickname'];
            $rest[$k]['mobile']       = $v['mobile'];
            $rest[$k]['email']        = $v['email'];
            $rest[$k]['birthday']     = $v['birthday'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['rolesData']    = RoleRepository::getRolesByAccountId($v['id'], ['id', 'name']);
            $rest[$k]['avatar']       = $v['avatar'] ? Base::fillUrl($v['avatar']) : '';
            $rest[$k]['roles']        = RoleRepository::getRolesIdsByAccountId($v['id']);
            $rest[$k]['rules']        = PermissionRepository::getAllPermissionsIdsByAccountId($v['id']);
            $rest[$k]['isShowDelete'] = self::isSuperAdmin($v['id']) ? 0 : 1;
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest ?? [];
        return $res;
    }


    public static function getList(int $limit): array
    {
        $query = Model::query();
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    /**
     * 添加
     * @param string $name
     * @param string $nickname
     * @param string $email
     * @param string $mobile
     * @param array $roles
     * @param string $password
     * @return int
     * @throws CommonException
     */
    public static function add(string $name, string $nickname, string $email, string $mobile, array $roles, string $password): int
    {
        try {
            $info['name']     = $name;
            $info['nickname'] = $nickname;
            $info['mobile']   = $mobile;
            $info['email']    = $email;
            $info['roles']    = $roles;
            $info['password'] = $password ? Hash::make(md5($password)) : Hash::make(md5(self::$defaultPassword));
            $resId            = Model::create($info)->id;
            foreach ($roles as $v) {
                AccountRole::create(['account_id' => $resId, 'role_id' => $v]);
            }
            return $resId;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }


    /**
     * 更新用户密码
     * @param string $password 明文
     * @param int $accountId
     * @return bool
     */
    public static function updatePassword(string $password, int $accountId): bool
    {
        $encryptPassword = Hash::make(md5($password));
        return Model::where('id', $accountId)->update(['password' => $encryptPassword]);
    }

    /**
     * 编辑用户信息
     * @param string $nickname
     * @param string $email
     * @param string $mobile
     * @param array $roles
     * @param int $accountId
     * @return bool
     */
    public static function edit(string $nickname, string $email, string $mobile, array $roles, int $accountId): bool
    {
        try {
            $info['nickname'] = $nickname;
            $info['mobile']   = $mobile;
            $info['email']    = $email;
            Model::where('id', $accountId)->update($info);
            AccountRole::where('account_id', $accountId)->delete();
            foreach ($roles as $v) {
                AccountRole::create(['account_id' => $accountId, 'role_id' => $v]);
            }
            return true;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 个人编辑
     * @param array $info
     * @param int $accountId
     * @return bool
     */
    public static function personalEdit(array $info, int $accountId): bool
    {
        try {
            return Model::where('id', $accountId)->update($info);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }


    public static function del(int $accountId): bool
    {
        Model::where('id', $accountId)->delete();
        return AccountRole::where('account_id', $accountId)->delete();
    }

    public static function updateAvatar(string $avatar, int $accountId): bool
    {
        return Model::where('id', $accountId)->update(['avatar' => $avatar]);
    }

    public static function getInfoByName(string $name): array
    {
        $one = Model::where('name', $name)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public static function getInfoByMobile(string $mobile): array
    {
        $one = Model::where('mobile', $mobile)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public static function getInfoByEmail(string $email): array
    {
        $one = Model::where('email', $email)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public static function getInfo(int $accountId): array
    {
        $one = Model::where('id', $accountId)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public static function isSuperAdmin(int $accountId): bool
    {
        return in_array($accountId, self::$supperAdminIds);
    }


}
