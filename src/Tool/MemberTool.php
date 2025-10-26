<?php
/**
 *  会员工具
 *
 */

namespace Antmin\Tool;


use Antmin\Http\Repositories\RequestLogRedis;
use Illuminate\Support\Facades\Redis;


class MemberTool
{

    /**
     * 统计默认 redis 的 key 前缀
     * @return string
     */
    protected static function getSetKey(): string
    {
        return RequestLogRedis::getStatKey() . '_member_set_stat';
    }



    /**
     * 统计 调用
     * @param int $memberId
     * @return void
     */
    public static function stat(int $memberId): void
    {
        if (empty($memberId)) {
            return;
        }
        $dayAt      = date('Y-m-d');
        $currentKey = self::getSetKey() . '_active_current';
        $oneDayKey  = self::getSetKey() . '_active_oneday_' . $dayAt;
        $res        = Redis::zAdd($currentKey, time(), $memberId);
        if ($res) {
            Redis::zAdd($oneDayKey, 1, $memberId);
        }
    }

    /**
     * 指定获取分钟内的数据
     * @param int $minute 最小1分钟，最大1440分钟
     * @return int
     */
    public static function getMinuteActiveTotal(int $minute): int
    {
        if ($minute < 1) {
            $minute = 1;
        }
        $curTime = time();
        $agoTime = $curTime - 60 * $minute;
        $endTime = $curTime - 3600 * 24;
        $currKey = self::getSetKey() . '_active_current';
        # 删除24小时之前的元素
        Redis::zremrangebyscore($currKey, 0, $endTime);
        # 返回区间数量
        return Redis::zCount($currKey, $agoTime, $curTime);
    }

    /**
     * 获取指定日期内用户活动总数
     * @param string $dayAt 最大15天之内日期
     * @return int
     */
    public static function getOneDayActiveTotal(string $dayAt = ''): int
    {
        if (empty($dayAt)) {
            $dayAt = date('Y-m-d');
        } else {
            $dayAt = date('Y-m-d', strtotime($dayAt));
        }
        $outAt     = date('Y-m-d', time() - 3600 * 24 * 15);
        $oneDayKey = self::getSetKey() . '_active_oneday_' . $dayAt;
        $outDayKey = self::getSetKey() . '_active_oneday_' . $outAt;
        # 删除15天前的数据
        Redis::del($outDayKey);
        # 返回指定日期的数量
        return Redis::zCard($oneDayKey);
    }

    /**
     * 统计 天 chart
     * @return array
     */
    public static function getDayChat(): array
    {
        $str  = self::getSetKey() . '_active_oneday_';
        $data = StatTool::getRecentDays(10);
        foreach ($data as $k => $v) {
            $key                = $str . $v;
            $chartData[$k]['x'] = $v;
            $chartData[$k]['y'] = Redis::zCard($key);
        }
        return $chartData ?? [];
    }


}
