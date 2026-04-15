<?php
/**
 * 邮箱
 */

namespace Antmin\Http\Services;

use Antmin\Common\Base;
use Exception;
use Antmin\Exceptions\CommonException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;

class EmailService
{

    protected const CACHE_OUT_TIME = 900;


    public static function test()
    {
        self::sendCode('1271292479@qq.com');
    }

    public static function sendCode(string $email): bool
    {
        try {
            $key  = md5($email);
            $code = Base::random(6);
            # 开始发送邮件

            Mail::raw("您的验证码是：{$code}，有效期15分钟", function ($message) use ($email) {
                $message->to($email)->subject('验证码');
            });

            # 发送成功，进行缓存
            Redis::setex($key, self::CACHE_OUT_TIME, $code);
            return true;

        } catch (Exception $e) {
            info($e->getMessage());
            throw new CommonException($e->getMessage());
        }
    }

    public static function verifyCode(string $email, string $code): bool
    {
        $key = md5($email);
        if (!Redis::exists($key)) {
            return false;
        }
        $_code = Redis::get($key);
        if ($_code !== $code) {
            return false;
        }
        return true;
    }


}

