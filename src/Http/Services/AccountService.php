<?php
/**
 * 账号
 */

namespace Antmin\Http\Services;

use Antmin\Common\Base;
use Antmin\Common\BaseImage;
use Antmin\Exceptions\CommonException;

use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\AccountRoleRepository;
use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\PermissionRepository;
use Antmin\Http\Repositories\TokenRepository;
use Exception;

class AccountService
{

    /**
     * 由 token 获取 accountId
     * @param string $token
     * @return int
     * @throws CommonException
     */
    public static function getAccountIdByToken(string $token): int
    {
        try {
            return TokenRepository::getIdByToken($token);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage(), [], -1, 401);
        }
    }

    /**
     * 账号基础信息
     * @param int $accountId
     * @return array
     */
    public static function getAccountBaseInfo(int $accountId): array
    {
        $account = AccountRepository::getInfo($accountId);
        if (empty($account)) {
            throw new CommonException('用户信息不存在');
        }
        $res['id']       = $account['id'];
        $res['name']     = $account['name'];
        $res['username'] = $account['nickname'];
        $res['mobile']   = $account['mobile'];
        $res['email']    = $account['email'];
        $res['birthday'] = $account['birthday'];
        $res['avatar']   = !empty($account['avatar']) ? $account['avatar'] : '';
        return $res;
    }

    /**
     * 账号列表
     * @param int $limit
     * @param int $opId
     * @return array
     */
    public static function accountList(int $limit, int $opId): array
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $res['users'] = AccountRepository::getFormatList($limit);
        $res['roles'] = RoleRepository::getFormatAccountList(99);
        $res['rules'] = PermissionRepository::getParentFormatToAccountList(99);
        return $res;
    }

    /**
     * 账号添加
     * @param string $nickname
     * @param string $email
     * @param string $mobile
     * @param array $roles
     * @param string $password
     * @param int $opId
     * @return int
     */
    public static function accountAdd(string $nickname, string $email, string $mobile, array $roles, string $password, int $opId): int
    {
        IsPasswordService::handleIsPassword($password);
        $name = Base::random(8, 'abcdefghijkmnpqrstuvwxyz');
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        if (empty($roles)) {
            throw new CommonException('角色值不存在');
        }
        if (!empty(AccountRepository::getInfoByName($name))) {
            throw new CommonException('账号名已存在');
        }
        if (!empty(AccountRepository::getInfoByMobile($mobile))) {
            throw new CommonException('手机号已存在');
        }
        if (!empty(AccountRepository::getInfoByEmail($email))) {
            throw new CommonException('邮箱已存在');
        }
        if (in_array(1, $roles)) {
            throw new CommonException('超级管理员角色不可以添加');
        }
        return AccountRepository::add($name, $nickname, $email, $mobile, $roles, $password);
    }

    /**
     * 账号编辑
     * @param string $nickname
     * @param string $email
     * @param string $mobile
     * @param array $roles
     * @param int $accountId
     * @param int $opId
     * @return bool
     */
    public static function accountEdit(string $nickname, string $email, string $mobile, array $roles, int $accountId, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $info = AccountRepository::getInfoByMobile($mobile);
        if ($info && $accountId != $info['id']) {
            throw new CommonException('手机号已存在');
        }
        $one = AccountRepository::getInfoByEmail($email);
        if ($one && $accountId != $one['id']) {
            throw new CommonException('邮箱已存在');
        }
        if (in_array(1, $roles)) {
            throw new CommonException('超级管理员角色不可以添加');
        }
        AccountRepository::edit($nickname, $email, $mobile, $roles, $accountId);
        return true;
    }

    /**
     * 个人编辑
     * @param string $filed
     * @param string $value
     * @param int $accountId
     * @return bool
     */
    public static function personalEdit(string $filed, string $value, int $accountId): bool
    {
        if (empty($filed) || empty($value)) {
            throw new CommonException('字段和值不能为空');
        }
        try {
            $one = AccountRepository::where($filed, $value)->get()->first();
            if (!empty($one) && $accountId != $one['id']) {
                if ($filed == 'mobile') {
                    throw new CommonException('手机号已存在');
                } elseif ($filed == 'email') {
                    throw new CommonException('邮箱已存在');
                } elseif ($filed == 'name') {
                    throw new CommonException('账号名称已存在');
                }
            }
            $info[$filed] = $value;
            return AccountRepository::personalEdit($info, $accountId);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }


    /**
     * 更新密码
     * @param string $password 明文
     * @param int $accountId
     * @param int $opId
     * @return bool
     */
    public static function accountEditPassword(string $password, int $accountId, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        return AccountRepository::updatePassword($password, $accountId);
    }

    /**
     * 更新状态
     * @param int $accountId
     * @param int $opId
     * @return bool
     */
    public static function accountEditStatus(int $accountId, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one    = AccountRepository::find($accountId);
        $status = empty($one['status']) ? 1 : 0;
        return AccountRepository::where('id', $accountId)->update(['status' => $status]);
    }


    /**
     * 账号详情
     * @param int $id
     * @param int $opId
     * @return array
     */
    public static function accountDetail(int $id, int $opId): array
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one              = AccountRepository::getInfoFormat($id);
        $one['rolesData'] = RoleRepository::getRolesByAccountId($id, ['id', 'name']);
        $one['rules']     = PermissionRepository::getAllPermissionsByAccountId($one['id']);
        return $one;
    }


    /**
     * 删除账号
     * @param int $id
     * @param int $opId
     * @return bool
     */
    public static function accountDel(int $id, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        if (AccountRepository::isSuperAdmin($id)) {
            throw new CommonException('超级管理员不可以删除');
        }
        AccountRoleRepository::where('account_id', $id)->delete();
        return AccountRepository::del($id);
    }

    /**
     * 上传头像
     * @param int $accountId
     * @return string
     */
    public static function uploadAvatar(int $accountId): string
    {
        $path    = '/upload/avatar/' . date('Ymd');
        $rest    = BaseImage::originUpload($_FILES, $path, '1024000', ['jpg', 'jpeg', 'png', 'gif']);
        $imgPath = $rest['imgPath'];
        $imgUrl  = $rest['imgUrl'];
        AccountRepository::updateAvatar($imgPath, $accountId);
        return $imgUrl;
    }


}
