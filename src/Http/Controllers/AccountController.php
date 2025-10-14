<?php
/**
 * 账号
 */

namespace Antmin\Http\Controllers;


use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\LoginService;
use Illuminate\Http\Request;

class AccountController extends BaseController
{


    /**
     * 登陆
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $requdata = $request->all();
        if (isset($requdata['username'])) {
            $name     = Base::getValue($request, 'username', '', 'required|max:50');
            $password = Base::getValue($request, 'password', '', 'required|max:50');
            $token    = LoginService::accountLogin($name, $password);
        } else {
            $mobile = Base::getValue($request, 'mobile', '', 'required');
            if (!Base::isMobile($mobile)) {
                throw new CommonException('手机号格式不正确');
            }
            $smscode = Base::getValue($request, 'captcha', '', 'required|max:6');
            $token   = LoginService::mobileLogin($mobile, $smscode);
        }
        return Base::sucJson('成功', ['token' => $token]);
    }



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
        return Base::sucJson('成功', $res);
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
        return Base::sucJson('成功');
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
        return Base::sucJson('成功');
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
        return Base::sucJson('成功');
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
        return Base::sucJson('成功');
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
        return Base::sucJson('成功', $res);
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
        return Base::sucJson('成功');
    }
}
