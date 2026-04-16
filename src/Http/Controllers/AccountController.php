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


    /**
     * 注册
     */
    public function register(Request $request)
    {
        $email    = Base::getValue($request, 'email', '', 'email');
        $captcha  = Base::getValue($request, 'captcha', '', 'required|min:6');
        $password = Base::getValue($request, 'password', '', 'required|min:8');

        $token = $this->loginService->register($email, $captcha, $password);
        return Base::sucJson('成功', ['token' => $token]);
    }

    /**
     * 发送邮件验证码
     */
    public function sendCodeByEmail(Request $request)
    {
        $email = Base::getValue($request, 'email', '', 'email');
        $this->loginService->sendCodeByEmail($email);
        return Base::sucJson('邮件验证码已发送');
    }


    /**
     * 【个人信息】编辑
     */
    public function personalInfoEdit($request)
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
        $this->accountService->personalEdit($filed, $value, $accountId);
        return Base::sucJson('成功');
    }


    /**
     * 【账号管理】列表
     */
    public function accountList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, 'pageSize', '', 'integer');
        $limit = $limit ?? 10;
        $res   = $this->accountService->accountList($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     *【账号管理】添加
     */
    public function accountAdd($request)
    {
        $opId = $request['accountId'];

        $request->validate([
            'username' => 'required|max:30',
            'mobile'   => 'required|mobile',
            'roles'    => 'required|array',
            'email'    => 'nullable|email',
            'password' => 'nullable|min:8'
        ]);

        $info['nickname'] = $request->input('username');
        $info['email']    = $request->input('email', $request->input('mobile') . '@163.com');
        $info['mobile']   = $request->input('mobile');
        $info['password'] = $request->input('password');
        $info['roles']    = $request->input('roles');

        $userId = $this->accountService->accountAdd($info, $opId);

        return Base::sucJson('账号添加成功', ['id' => $userId]);
    }

    /**
     *【账号管理】编辑
     */
    public function accountEdit($request)
    {
        $opId = $request['accountId'];
        $request->validate([
            'id'       => 'required|integer',
            'username' => 'required|max:50',
            'email'    => 'required|email',
            'mobile'   => 'required|regex:/^1[3-9]\d{9}$/',
            'roles'    => 'required|array'
        ]);
        $id               = $request->input('id');
        $info['nickname'] = $request->input('username');
        $info['email']    = $request->input('email');
        $info['mobile']   = $request->input('mobile');
        $info['roles']    = $request->input('roles');

        $this->accountService->accountEdit($info, $id, $opId);

        return Base::sucJson('账号编辑成功');
    }

    /**
     *【账号管理】状态开关
     */
    public function accountEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->editStatus($id, $opId);
        return Base::sucJson('状态更新成功');
    }

    /**
     * 【账号管理】删除
     */
    public function accountDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->accountDel($id, $opId);
        return Base::sucJson('删除成功');
    }

    /**
     * 【账号管理】重置密码
     */
    public function reInitPassword($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->reInitPassword($id);
        return Base::sucJson('密码重置成功');
    }


}
