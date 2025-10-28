<?php
/**
 *  请求日志 工具
 */

namespace Antmin\Tool;


use Antmin\Http\Repositories\RequestLogRedis;

class LogRequestTool
{


    /**
     * 统计 当前月总量
     * @return int
     */
    public static function getCurrentMonthTotal(): int
    {
        $key = RequestLogRedis::getStatKey();
        return StatTool::getEveryMonthStat($key);
    }

    /**
     * 统计 当前总量
     * @return int
     */
    public static function getAllTotal(): int
    {
        $key = RequestLogRedis::getStatKey();
        return StatTool::getTotalStat($key);
    }

    /**
     * 统计 今日请求总量
     * @return int
     */
    public static function getTodayTotal(): int
    {
        $key = RequestLogRedis::getStatKey();
        return StatTool::getEveryDayStat($key);
    }

    /**
     * 统计 get 今日不同的客户端请求量
     * @param string $client
     * @return int
     */
    public static function getTodayClientTotal(string $client): int
    {
        if (empty($client)) {
            return 0;
        }
        $key = RequestLogRedis::getStatKey() . '_' . $client;
        return StatTool::getEveryDayStat($key);
    }

    /**
     * 统计 set  今日不同的客户端请求量
     * @param string $client
     * @return void
     */
    public static function setTodayClientTotal(string $client)
    {
        if (empty($client)) {
            return;
        }
        $key = RequestLogRedis::getStatKey() . '_' . $client;
        StatTool::setEveryDayStat($key);
    }

    /**
     * 统计 昨日请求量
     * @return int
     */
    public static function getYesTodayTotal(): int
    {
        $yestoday = date('Y-m-d', time() - 86400);
        $key      = RequestLogRedis::getStatKey() . ':' . $yestoday;
        return StatTool::getEveryDayStat($key, false);
    }

    /**
     * 统计 小时 chart
     * @return array
     */
    public static function getHourChart(): array
    {
        $str = RequestLogRedis::getStatKey();
        $arr = StatTool::getRecentHours(12);
        foreach ($arr as $k => $v) {
            $key                = $str . ':' . $v;
            $chartData[$k]['x'] = $v;
            $chartData[$k]['y'] = StatTool::getEveryHourStat($key, 0);
        }
        return $chartData ?? [];
    }

    /**
     * 统计 天 chart
     * @return array
     */
    public static function getDayChart(): array
    {
        $str = RequestLogRedis::getStatKey();
        $arr = StatTool::getRecentDays(10);
        foreach ($arr as $k => $v) {
            $key                = $str . ':' . $v;
            $chartData[$k]['x'] = $v;
            $chartData[$k]['y'] = StatTool::getEveryDayStat($key, 0);
        }
        return $chartData ?? [];
    }

    /**
     * 统计 月 chart
     * @return array
     */
    public static function getMonthChart(): array
    {
        $str = RequestLogRedis::getStatKey();
        $arr = StatTool::getRecentMonths();
        foreach ($arr as $k => $v) {
            $key                   = $str . ':' . $v;
            $chartData[$k]['name'] = $v;
            $chartData[$k]['x']    = StatTool::getEveryMonthStat($key, 0);
        }
        return $chartData ?? [];
    }


}
