<?php

namespace Antmin\Traits;


use Antmin\Tool\CacheTool;

trait CacheTraits
{

    /**
     * 缓存数据 获取
     * @param string $cacheKey
     * @param callable $callback
     * @param int $outtime 秒
     * @return array
     */
    public static function cacheTraitsGet(string $cacheKey, callable $callback, int $outtime = 0): array
    {
        $key = CacheTool::getPrefix($cacheKey, self::class);
        $res = CacheTool::getArrCache($key);
        if ($res) {
            return $res;
        }
        $data = $callback(); # 调用传入的闭包
        CacheTool::setArrCache($key, $data, $outtime);
        return $data ?? [];
    }

    /**
     * 缓存数据 删除
     * @param string $cacheKey
     * @return void
     */
    public static function cacheTraitsDel(string $cacheKey): void
    {
        $key = CacheTool::getPrefix($cacheKey, self::class);
        CacheTool::delArrCache($key);
    }

    /**
     * 指定头部 批量删除
     * @param $cacheKey
     */
    public static function cacheTraitsClear($cacheKey): void
    {
        $key = CacheTool::getPrefix($cacheKey, self::class);
        CacheTool::clearPrefix($key);
    }


}
