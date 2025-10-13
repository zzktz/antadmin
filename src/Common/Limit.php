<?php
/**
 *  限流器
 */

namespace Antmin\Common;

use Illuminate\Support\Facades\Redis;

class Limit
{
    /**
     * 限流器
     * @param string $key
     * @param int $max
     * @param int $keepSecond 秒
     * @return bool  true通过  false禁止
     */
    public static function handle(string $key, int $max, int $keepSecond): bool
    {
        return Redis::throttle("Limit:" . $key)->allow($max)->every($keepSecond)
            ->then(function () {    # 正常访问
                return true;
            }, function () {        # 触发上限
                return false;
            });
    }


    /**
     * 使用方法
     * $key = 'FaceAttest_memberId_' . $memberId;
     * $res = Limit::handle($key, 1, 5);
     * if ($res == false) {
     *      throw new CommonException('您访问太快了，稍后再试');
     * }
     */

}
