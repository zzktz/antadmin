<?php
/**
 * 邮箱
 */

namespace Antmin\Http\Services;

use Exception;
use Antmin\Exceptions\CommonException;
use Illuminate\Support\Facades\Redis;

class EmailService
{

    protected const CACHE_OUT_TIME = 900;


    public function sendCode(string $email): bool
    {
        try {
            $key  = md5($email);
            $code = num_random(6);
            # 开始发送邮件


            # 发送成功，进行缓存
            Redis::setex($key, self::CACHE_OUT_TIME, $code);
            return true;

        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public function verifyCode(string $email, string $code): bool
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

