<?php
/**
 * 账号
 */

namespace Antmin\Http\Controllers;

use Exception;
use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\LoginService;
use Illuminate\Http\Request;

class AccountController extends BaseController
{

    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountService $accountService,
        protected LoginService   $loginService
    )
    {
        # 可在此添加中间件
        # $this->middleware('auth')->except(['login']);
    }


    /**
     * 登陆
     */
    public function login(Request $request)
    {
        try {
            if ($request->has('username')) {
                # 用户名密码登录
                $request->validate([
                    'username' => 'required|max:50',
                    'password' => 'required|max:50'
                ]);

                $name     = $request->input('username');
                $password = $request->input('password');
                $token    = $this->loginService->accountLogin($name, $password);
            } else {
                # 手机验证码登录
                $request->validate([
                    'mobile'  => 'required|mobile',
                    'captcha' => 'required|max:6'
                ]);

                $mobile  = $request->input('mobile');
                $smscode = $request->input('captcha');

                $token = $this->loginService->mobileLogin($mobile, $smscode);
            }

            return Base::sucJson('成功', ['token' => $token]);
        } catch (Exception $e) {
            # 系统异常
            throw new CommonException('登录失败: ' . $e->getMessage());
        }
    }




}
