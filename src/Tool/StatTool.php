<?php

namespace Antmin\Tool;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class StatTool
{

    /**
     * 小时 计入
     * @param string $key
     * @return void
     */
    public static function setEveryHourStat(string $key): void
    {
        $str = date('Y-m-d-H');
        $key = $key . ':' . $str;
        Redis::incr($key);
        Redis::expire($key, 3600 * 12);
    }

    /**
     * 小时 获取
     * @param string $key
     * @param bool $isNow 是否当前小时
     * @return int
     */
    public static function getEveryHourStat(string $key, bool $isNow = true): int
    {
        if ($isNow) {
            $str = date('Y-m-d-H');
            $key = $key . ':' . $str;
        }
        $res = Redis::get($key);
        return $res ? intval($res) : 0;
    }

    /**
     * 天 计入
     * @param string $key
     * @return void
     */
    public static function setEveryDayStat(string $key): void
    {
        $str = date('Y-m-d');
        $key = $key . ':' . $str;
        Redis::incr($key);
    }

    /**
     * 天 获取
     * @param string $key
     * @param bool $isNow 是否当前天
     * @return int
     */
    public static function getEveryDayStat(string $key, bool $isNow = true): int
    {
        if ($isNow) {
            $str = date('Y-m-d');
            $key = $key . ':' . $str;
        }
        $res = Redis::get($key);
        return $res ? intval($res) : 0;
    }

    /**
     * 月 计入
     * @param string $key
     * @return void
     */
    public static function setEveryMonthStat(string $key): void
    {
        $str = date('Y-m');
        $key = $key . ':' . $str;
        Redis::incr($key);
    }

    /**
     * 月 获取
     * @param string $key
     * @param bool $isNow 是否当前月
     * @return int
     */
    public static function getEveryMonthStat(string $key, bool $isNow = true): int
    {
        if ($isNow) {
            $str = date('Y-m');
            $key = $key . ':' . $str;
        }
        $res = Redis::get($key);
        return $res ? intval($res) : 0;
    }

    /**
     * 年 计入
     * @param string $key
     * @return void
     */
    public static function setEveryYearStat(string $key): void
    {
        $str = date('Y');
        $key = $key . ':' . $str;
        Redis::incr($key);
    }

    /**
     * 年 获取
     * @param string $key
     * @param bool $isNow 是否当前月
     * @return int
     */
    public static function getEveryYearStat(string $key, bool $isNow = true): int
    {
        if ($isNow) {
            $str = date('Y');
            $key = $key . ':' . $str;
        }
        $res = Redis::get($key);
        return $res ? intval($res) : 0;
    }

    /**
     * 总共 计入
     * @param string $key
     * @return void
     */
    public static function setTotalStat(string $key): void
    {
        $key = $key . ':total';
        Redis::incr($key);
    }

    /**
     * 总共 获取
     * @param string $key
     * @return int
     */
    public static function getTotalStat(string $key): int
    {
        $key = $key . ':total';
        $res = Redis::get($key);
        return $res ? intval($res) : 0;
    }

    /**
     * 最近几个小时
     * @param int $num
     * @return array
     */
    public static function getRecentHours(int $num): array
    {
        $hours = [];
        $now   = Carbon::now();
        # 从当前时间开始
        for ($i = 1; $i < $num; $i++) {
            $hour = $now->subHour()->format('Y-m-d-H');
            array_unshift($hours, $hour); # 将新的时间戳添加到数组开头
        }
        $hours[] = date('Y-m-d-H');
        return $hours;
    }

    /**
     * 最近几天
     * @param int $num
     * @return array
     */
    public static function getRecentDays(int $num): array
    {
        $days = [];
        $now  = Carbon::now();
        for ($i = 1; $i < $num; $i++) {
            $day = $now->subDay()->format('Y-m-d');
            array_unshift($days, $day);
        }
        $days[] = date('Y-m-d');
        return $days;
    }

    /**
     * 最近几月
     * @param int $num
     * @return array
     */
    public static function getRecentMonths(int $num = 6): array
    {
        $months = [];
        $now    = Carbon::now();
        for ($i = 1; $i < $num; $i++) {
            $month = $now->subMonthNoOverflow()->format('Y-m');
            array_unshift($months, $month);
        }
        $months[] = date('Y-m');
        return $months;
    }


    /**
     * 最近几年
     * @param int $num
     * @return array
     */
    public static function getRecentYears(int $num): array
    {
        $years = [];
        $now   = Carbon::now();
        for ($i = 1; $i < $num; $i++) {
            $year = $now->subYearsNoOverflow()->format('Y');
            array_unshift($years, $year);
        }
        $years[] = date('Y');
        return $years;
    }



}