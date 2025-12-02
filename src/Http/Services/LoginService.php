<?php
/**
 * 登录
 */

namespace Antmin\Http\Services;

use Antmin\Common\Base;
use Antmin\Common\Limit;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\SmsRepository;
use Antmin\Http\Repositories\TokenRepository;
use Antmin\Http\Repositories\AccountRepository;
use Illuminate\Support\Facades\Hash;
use Exception;

class LoginService
{


    /**
     * 账号登陆
     * @param string $name
     * @param string $password
     * @return string
     */
    public static function accountLogin(string $name, string $password): string
    {
        # 安全检查
        SafeService::checking();
        try {
            if (Base::isMobile($name)) {
                $info = AccountRepository::getInfoByMobile($name);
            } else {
                $info = AccountRepository::getInfoByName($name);
            }

            if (empty($info)) {
                throw new CommonException('账户或密码错误');
            }
            $accountId = $info['id'];
            $_password = $info['password'];

            if (!Hash::check($password, $_password)) {
                throw new CommonException('账户或密码错误');
            }
            throw new CommonException('111111');
            $token = TokenRepository::getTokenById($accountId);
            throw new CommonException('22222');
            # 成功
            SafeService::flagSuccess();
            return $token;
        } catch (Exception $e) {
            # 失败
            $num = SafeService::flagFail();
            $msg = '第' . $num . '次' . $e->getMessage() . '，' . SafeService::getMaxTip();
            throw new CommonException($msg);
        }
    }

    /**
     * 短信登陆
     * @param string $mobile
     * @param string $smscode
     * @return string
     */
    public static function mobileLogin(string $mobile, string $smscode): string
    {
        $key = 'login_sms_check_mobile_' . $mobile;
        if (!Limit::handle($key, 1, 10)) {
            throw new CommonException('您访问太快了，稍后再试');
        }
        $res = SmsRepository::checkSmsCode($mobile, $smscode, 300);
        if (!$res) {
            throw new CommonException('短信验证码错误');
        }
        $info = AccountRepository::getInfoByMobile($mobile);
        if (empty($info)) {
            throw new CommonException('手机号不存在');
        }
        return TokenRepository::getTokenById($info['id']);
    }


    public static function accountLogout(): void
    {
        //TokenRepository::destroyToken();
    }


}
