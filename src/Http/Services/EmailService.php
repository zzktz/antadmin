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

    protected const CACHE_OUT_TIME  = 900;
    protected const ONE_DAY_MAX_NUM = 10; # 一天最大发送量

    public static function test()
    {
        self::sendCode('1271292479@qq.com');
    }

    public static function sendCode(string $email): bool
    {
        try {
            $key     = md5($email);
            $code    = Base::random(6);
            $flagMin = $key . '_flag_min';

            # 每分钟限制
            if (Redis::get($flagMin)) {
                throw new CommonException('请求太频繁，最大允许每分获取一次验证码');
            }

            # 每日限制（补全部分）
            $dayKey   = $key . '_day_' . date('Ymd');
            $dayCount = (int)Redis::get($dayKey);
            if ($dayCount >= self::ONE_DAY_MAX_NUM) {
                throw new CommonException('今日验证码发送次数已达上限，请明天再试');
            }

            # 发送邮件
            Mail::raw("您的验证码是：{$code}，有效期15分钟", function ($message) use ($email) {
                $message->to($email)->subject('验证码');
            });

            # 发送成功，缓存验证码、每分钟标识、每日计数
            Redis::setex($key, self::CACHE_OUT_TIME, $code);
            Redis::setex($flagMin, 60, 1);
            Redis::incr($dayKey);
            Redis::expire($dayKey, 86400); # 24小时后自动清除

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

