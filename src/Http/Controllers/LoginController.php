<?php
/**
 * 登陆
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\LoginService;
use Illuminate\Http\Request;


class LoginController extends BaseController
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


}
