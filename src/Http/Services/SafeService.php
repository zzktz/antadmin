<?php
/**
 * 安全控制
 */

namespace Antmin\Http\Services;

use Antmin\Exceptions\CommonException;
use Illuminate\Support\Facades\Redis;

class SafeService
{

    protected static int $lockTime = 30;       # 锁定时间 分钟
    protected static int $attemptLimit = 5;    # 尝试次数

    /**
     * 检查
     * @return void
     * @throws CommonException
     */
    public static function checking(): void
    {
        $key   = self::getKey();
        $redis = Redis::connection('default');
        # 删除
        $hasMax = $redis->zcard($key) + 1; # 集合现有元素数量
        $last   = $redis->zrevrange($key, 0, 0);
        if (empty($last[0])) {
            return;
        }
        $time   = intval($last[0] / 1000);
        $minute = intval(($time - time()) / 60) + 1;
        # 有序集合的最大值
        if ($hasMax >= self::$attemptLimit) {
            $msg = '超过' . $hasMax . '次密码错误，请' . $minute . '分钟后重试';
            throw new CommonException($msg);
        }
    }

    /**
     * 标记 失败
     * @return int
     */
    public static function flagFail(): int
    {
        $key   = self::getKey();
        $redis = Redis::connection('default');

        # 毫秒
        $milliseconds = intval(microtime(true) * 1000);
        $resultsecond = $milliseconds + (self::$lockTime * 60 * 1000);
        $redis->zadd($key, $milliseconds, $resultsecond); # 有序集合
        $redis->expire($key, 60 * self::$lockTime);
        # 返回先有次数
        return $redis->zcard($key);
    }

    /**
     * 标记 成功
     * @return void
     */
    public static function flagSuccess(): void
    {
        $key   = self::getKey();
        $redis = Redis::connection('default');
        $redis->del($key);
    }

    /**
     * 最大提示信息
     * @return string
     */
    public static function getMaxTip(): string
    {
        return '最大尝试' . self::$attemptLimit . '次后将锁定' . self::$lockTime . '分钟';
    }

    /**
     * 用户 key
     * @return string
     */
    private static function getKey(): string
    {
        $userAgent = request()->header('user-agent');
        $uid       = md5($userAgent);
        return "account_safe:" . $uid;
    }


}
