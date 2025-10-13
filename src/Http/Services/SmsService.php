<?php
/**
 * 短信
 */

namespace Antmin\Http\Services;


use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\SmsRepository;
use Antmin\Exceptions\CommonException;

class SmsService
{

    public static function sendSmsCode(string $mobile, string $ip, string $type): bool
    {
        if (!isMobile($mobile)) {
            throw new CommonException('手机号不正确');
        }
        $typeIds = SmsRepository::getSendType();
        if (!in_array($type, $typeIds)) {
            throw new CommonException('发送类型不正确');
        }
        $one = AccountRepository::getInfoByMobile($mobile);
        if (in_array($type, ['login', 'fixPassword', 'forgetPassword']) && empty($one)) {
            throw new CommonException('手机号未注册');
        }
        if ($type == 'reg' && !empty($one)) {
            throw new CommonException('手机号已注册');
        }
        SmsRepository::sendSmsCode($mobile, $ip);
        return true;
    }


}
