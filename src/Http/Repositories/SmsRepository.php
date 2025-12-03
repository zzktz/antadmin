<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\SmsReport;
use Antmin\Third\SmsThird;
use Exception;
# use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\Connection as RedisConnection;

class SmsRepository # 不再继承 Model，改为一个纯粹的服务类
{
    # 配置定义为类常量
    protected const CACHE_OUT_TIME   = 600;
    protected const CACHE_CONNECTION = 'default';
    protected const CODE_MAX_DIGITS  = 4;
    protected const CODE_DEV_DEFAULT = '8666';
    protected const CODE_SEND_TYPE   = ['reg', 'login', 'fixPassword', 'forgetPassword'];

    # 通过构造函数注入依赖
    public function __construct(
        protected RedisConnection $redis, # 注入 Redis 连接
        protected SmsReport       $smsReportModel # 注入 Model 实例
    )
    {
        # 可以在构造函数里初始化连接，但这里通过注入已经完成
        # $this->redis = Redis::connection(self::CACHE_CONNECTION);
    }

    public function getSendType(): array
    {
        return self::CODE_SEND_TYPE;
    }

    public function sendSmsCode(string $mobile, string $ip): int
    {
        $tplId   = '';
        $code    = num_random(self::CODE_MAX_DIGITS);
        $data[0] = $code;

        SmsThird::send($mobile, $tplId, $data);

        $key = $this->getCacheKey($mobile);
        $this->redis->setex($key, self::CACHE_OUT_TIME, $code);

        return $this->addReport($mobile, $code, $ip);
    }

    public function checkSmsCode(string $mobile, string $smsCode, bool $isSingle = true): bool
    {
        if ($smsCode == self::CODE_DEV_DEFAULT) {
            return true;
        }

        $key = $this->getCacheKey($mobile);
        if (!$this->redis->exists($key)) {
            return false;
        }

        $res = $this->redis->get($key);
        if ($res !== $smsCode) {
            return false;
        }

        if ($isSingle) {
            $this->redis->del($key);
        }
        return true;
    }

    private function addReport(string $mobile, string $code, string $ip, string $msg = ''): int
    {
        try {
            return $this->smsReportModel->newQuery()->create([
                'ip'     => $ip,
                'msg'    => $msg,
                'code'   => $code,
                'mobile' => $mobile,
            ])->id;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    private function getCacheKey(string $key): string
    {
        return Base::utf8_str_replace(static::class, '\\', '_') . '_' . $key;
    }



}