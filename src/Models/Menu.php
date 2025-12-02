<?php

namespace Antmin\Models;

use Antmin\Tool\CacheTool;
use Antmin\Traits\CacheTraits;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use CacheTraits;

    protected $table = 'system_menu';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        # 监听创建事件
        static::created(function () {
            self::clearAllCacheData();
        });
        # 监听更新事件
        static::updated(function () {
            self::clearAllCacheData();
        });
        # 监听删除事件
        static::deleted(function () {
            self::clearAllCacheData();
        });
    }

    /**
     * 获取全部分类的缓存
     * @return array
     */
    public static function getAllCacheData(): array
    {
        $cacheKey = 'All';
        # 使用匿名函数来获取数据
        $getData = function () {
            # 从数据库中查询数据
            $data = self::all();
            return is_null($data) ? [] : $data->toArray();
        };
        # 获取数据
        return self::cacheTraitsGet($cacheKey, $getData);
    }

    /**
     * 清除全部的缓存
     * @return void
     */
    public static function clearAllCacheData(): void
    {
        $cacheKey = 'All';
        self::cacheTraitsDel($cacheKey);
    }

}
