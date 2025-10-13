<?php
/**
 * 首页
 */

namespace Antmin\Http\Controllers;


use Antmin\Common\Base;
use Antmin\Http\Services\SmsService;
use Antmin\Http\Services\LoginService;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\PermissionsService;


class HomeController extends BaseController
{


    /**
     * 短信码
     * @param $request
     * @return mixed
     */
    public static function getSmsCode($request)
    {
        $type   = Base::getValue($request, 'type', '', 'required|max:100');
        $mobile = Base::getValue($request, 'mobile', '', 'required|mobile');
        $ipAddr = $request->getClientIp();
        SmsService::sendSmsCode($mobile, $ipAddr, $type);
        return Base::sucJson('ok');
    }


    public static function logout()
    {
        LoginService::accountLogout();
        return Base::sucJson('成功退出');
    }

    public static function step2Code()
    {
        return Base::sucJson('成功');
    }

    /**
     * 用户基础信息
     * @param $request
     * @return mixed
     */
    public static function getUserInfo($request)
    {
        $accountId   = $request['accountId'];
        $res         = AccountService::getAccountBaseInfo($accountId);
        $permissions = PermissionsService::handleGetPermissionByAccountId($accountId);
        $res['role'] = $permissions;
        return Base::sucJson('成功', $res);
    }

    /**
     * 个人信息编辑
     * @param $request
     * @return mixed
     */
    public static function personalInfoEdit($request)
    {
        $accountId = $request['accountId'];
        $email     = Base::getValue($request, 'email', '', 'email');
        $mobile    = Base::getValue($request, 'mobile', '', 'mobile');
        $nickname  = Base::getValue($request, 'nickname', '', 'alpha_dash|max:50');
        $birthday  = Base::getValue($request, 'birthday', '', 'date_format:Y-m-d');
        if (!empty($mobile)) {
            $filed = 'mobile';
            $value = $mobile;
        } elseif (!empty($nickname)) {
            $filed = 'nickname';
            $value = $nickname;
        } elseif (!empty($email)) {
            $filed = 'email';
            $value = $email;
        } elseif (!empty($birthday)) {
            $filed = 'birthday';
            $value = $birthday;
        } else {
            $filed = '';
            $value = '';
        }
        AccountService::personalEdit($filed, $value, $accountId);
        return Base::sucJson('成功');
    }


}