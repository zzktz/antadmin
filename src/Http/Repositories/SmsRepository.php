<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\SmsReport as Model;
use Antmin\Third\SmsThird;
use Exception;
use Illuminate\Support\Facades\Redis;

class SmsRepository extends Model
{

    protected static $cacheOutTime    = 600; # 短信过期时间 秒
    protected static $cacheConnection = 'default';
    protected static $codeMaxDigits   = 4;
    protected static $codeDevDefault  = '8666';
    protected static $codeSendType    = ['reg', 'login', 'fixPassword', 'forgetPassword'];


    public static function getSendType(): array
    {
        return self::$codeSendType;
    }

    public static function sendSmsCode(string $mobile, string $ip): int
    {
        $tplId   = '';
        $code    = num_random(self::$codeMaxDigits);
        $data[0] = $code;
        # 发送
        SmsThird::send($mobile, $tplId, $data);
        # 缓存
        $key   = self::getCacheKey($mobile);
        $redis = Redis::connection(self::$cacheConnection);
        $redis->setex($key, self::$cacheOutTime, $code);
        return self::addReport($mobile, $code, $ip);
    }


    public static function checkSmsCode(string $mobile, string $smsCode, bool $isSingle = true): bool
    {
//        if (Base::isDev() && $smsCode == self::$codeDevDefault) {
//            return true;
//        }
        if ($smsCode == self::$codeDevDefault) {
            return true;
        }
        $key   = self::getCacheKey($mobile);
        $redis = Redis::connection(self::$cacheConnection);
        if (empty($redis->exists($key))) {
            return false;
        }
        $res = $redis->get($key);
        if (empty($res)) {
            return false;
        }
        if ($res !== $smsCode) {
            return false;
        }
        if ($isSingle) {
            $redis->del($key);
        }
        return true;
    }


    private static function addReport(string $mobile, string $code, string $ip, string $msg = ''): int
    {
        try {
            $in['ip']     = $ip;
            $in['msg']    = $msg;
            $in['code']   = $code;
            $in['mobile'] = $mobile;
            return Model::create($in)->id;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }


    private static function getCacheKey(string $key): string
    {
        return Base::utf8_str_replace(self::class, '\\', '_') . '_' . $key;
    }


}
