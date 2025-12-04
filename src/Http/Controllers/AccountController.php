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
     * 账号添加
     */
    public function accountAdd(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'username' => 'required|max:50',
                'email'    => 'nullable|email',
                'mobile'   => 'required|regex:/^1[3-9]\d{9}$/',
                'password' => 'required|min:6',
                'roles'    => 'required|array'
            ]);

            $nickname = $request->input('username');
            $email    = $request->input('email', $request->input('mobile') . '@163.com');
            $mobile   = $request->input('mobile');
            $password = $request->input('password');
            $roles    = $request->input('roles');

            $userId = $this->accountService->accountAdd(
                $nickname, $email, $mobile, $roles, $password, $opId
            );

            return Base::sucJson('账号添加成功', ['id' => $userId]);
        } catch (Exception $e) {
            throw new CommonException('添加账号失败: ' . $e->getMessage());
        }
    }

    /**
     * 账号编辑
     */
    public function accountEdit(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'id'       => 'required|integer',
                'username' => 'required|max:50',
                'email'    => 'nullable|email',
                'mobile'   => 'required|regex:/^1[3-9]\d{9}$/',
                'roles'    => 'required|array'
            ]);

            $id       = (int)$request->input('id');
            $nickname = $request->input('username');
            $email    = $request->input('email', $request->input('mobile') . '@163.com');
            $mobile   = $request->input('mobile');
            $roles    = $request->input('roles');

            $this->accountService->accountEdit(
                $nickname, $email, $mobile, $roles, $id, $opId
            );

            return Base::sucJson('账号编辑成功');
        } catch (Exception $e) {
            throw new CommonException('编辑账号失败: ' . $e->getMessage());
        }
    }


    /**
     * 账号更新密码
     */
    public function accountEditPassword(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'id'       => 'required|integer',
                'password' => 'required|min:6'
            ]);

            $id       = (int)$request->input('id');
            $password = $request->input('password');

            $this->accountService->accountEditPassword($password, $id, $opId);

            return Base::sucJson('密码更新成功');
        } catch (Exception $e) {
            throw new CommonException('更新密码失败: ' . $e->getMessage());
        }
    }

    /**
     * 编辑账号状态
     */
    public function accountEditStatus(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'id' => 'required|integer'
            ]);

            $id = (int)$request->input('id');

            $this->accountService->accountEditStatus($id, $opId);

            return Base::sucJson('状态更新成功');
        } catch (Exception $e) {
            throw new CommonException('更新状态失败: ' . $e->getMessage());
        }
    }


    /**
     * 账号详情
     */
    public function accountDetail(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'id' => 'required|integer'
            ]);

            $id = (int)$request->input('id');

            $res = $this->accountService->accountDetail($id, $opId);

            return Base::sucJson('成功', $res);
        } catch (Exception $e) {
            throw new CommonException('获取账号详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 账号删除
     */
    public function accountDel(Request $request)
    {
        try {
            $opId = $this->getCurrentAccountId($request);

            $request->validate([
                'id' => 'required|integer'
            ]);

            $id = (int)$request->input('id');

            $this->accountService->accountDel($id, $opId);

            return Base::sucJson('账号删除成功');
        } catch (Exception $e) {
            throw new CommonException('删除账号失败: ' . $e->getMessage());
        }
    }


}
