<?php
/**
 * 账号
 */

namespace Antmin\Http\Controllers;


use Antmin\Common\Base;
use Antmin\Http\Services\AccountService;

class AccountController extends BaseController
{


    /**
     * 账号列表
     * @param $request
     * @return mixed
     */
    public static function accountList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, "pageSize", '', 'integer');
        $limit = $limit ?? 10;
        $res   = AccountService::accountList($limit, $opId);
        return sucJson('成功', $res);
    }

    /**
     * 账号添加
     * @param $request
     * @return mixed
     */
    public static function accountAdd($request)
    {
        $opId     = $request['accountId'];
        $nickname = Base::getValue($request, 'username', '', 'required|max:50');
        $email    = Base::getValue($request, 'email', '', 'email');
        $mobile   = Base::getValue($request, 'mobile', '', 'required|mobile');
        $password = Base::getValue($request, 'password', '', 'required');
        $roles    = Base::getValue($request, 'roles', '', 'required');
        $email    = $email ?? $mobile . '@163.com';
        
        AccountService::accountAdd($nickname, $email, $mobile, $roles, $password, $opId);
        return sucJson('成功');
    }

    /**
     * 账号编辑
     * @param $request
     * @return mixed
     */
    public static function accountEdit($request)
    {
        $opId     = $request['accountId'];
        $id       = Base::getValue($request, "id", '', 'required|integer');
        $nickname = Base::getValue($request, 'username', '', 'required|max:50');
        $email    = Base::getValue($request, 'email', '', 'email');
        $mobile   = Base::getValue($request, 'mobile', '', 'required|mobile');
        $roles    = Base::getValue($request, 'roles', '', 'required');
        $email    = $email ?? $mobile . '@163.com';
        AccountService::accountEdit($nickname, $email, $mobile, $roles, $id, $opId);
        return sucJson('成功');
    }

    /**
     * 账号更新密码
     * @param $request
     * @return mixed
     */
    public static function accountEditPassword($request)
    {
        $opId     = $request['accountId'];
        $id       = Base::getValue($request, "id", '', 'required|integer');
        $password = Base::getValue($request, 'password', '', 'required');
        AccountService::accountEditPassword($password, $id, $opId);
        return sucJson('成功');
    }

    /**
     * 编辑列表中状态
     * @param $request
     * @return mixed
     */
    public static function accountEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, "id", '', 'required|integer');
        AccountService::accountEditStatus($id, $opId);
        return sucJson('成功');
    }

    /**
     * 账号详情
     * @param $request
     * @return mixed
     */
    public static function accountDetail($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, "id", '', 'required|integer');
        $res  = AccountService::accountDetail($id, $opId);
        return sucJson('成功', $res);
    }

    /**
     * 账号删除
     * @param $request
     * @return mixed
     */
    public static function accountDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, "id", '', 'required|integer');
        AccountService::accountDel($id, $opId);
        return sucJson('成功');
    }
}
