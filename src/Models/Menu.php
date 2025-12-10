<?php

namespace Antmin\Models;


use Log;
use Throwable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class Menu extends Model
{
    protected $table   = 'system_menu';
    protected $guarded = [];

    # 定义缓存键前缀和基础TTL
    const CACHE_PREFIX = 'antmin:menu:'; # 增加项目标识
    const DEFAULT_TTL  = 3600; # 1小时

    protected static function boot()
    {
        parent::boot();

        # 使用 saved 事件（同时涵盖 created 和 updated）
        static::saved(function () {
            static::clearAllCacheData();
        });

        static::deleted(function () {
            static::clearAllCacheData();
        });
    }

    /**
     * 获取全部分类的缓存（基础版）
     */
    public function getAllCacheData(): array
    {
        $cacheKey = $this->generateCacheKey('all');
        # 为TTL增加随机扰动，防止雪崩 (3300 ~ 3900秒)
        $ttl = self::DEFAULT_TTL + rand(-300, 300);

        return Cache::remember($cacheKey, $ttl, function () {
            # 建议：按需选择字段，提升效率
            return self::all()->toArray() ?? [];
        });
    }

    /**
     * 清除全部的缓存
     */
    public static function clearAllCacheData(): void
    {
        $instance = new static; # 创建实例以调用非静态方法
        $cacheKey = $instance->generateCacheKey('all');
        Cache::forget($cacheKey);

        # 可选：如果需要清除所有相关的菜单缓存，可以使用缓存标签
        # 先确认你的缓存驱动支持标签（如 Redis）
        # Cache::tags('menu')->flush();
    }

    /**
     * 安全获取缓存（防击穿）
     */
    public function getAllCacheDataSafely(): array
    {
        $cacheKey = $this->generateCacheKey('all');
        $lockKey  = $cacheKey . ':lock';
        $lock     = null;

        # 首先尝试快速获取已存在的缓存
        $cached = Cache::get($cacheKey);
        if (!is_null($cached)) {
            return $cached;
        }

        try {
            $lock = Cache::lock($lockKey, 15);# 稍微延长锁超时时间
            # 尝试获取锁，等待最多2秒
            if ($lock->block(2)) {
                # 再次检查，防止在等待锁期间缓存已被其他进程构建
                $cached = Cache::get($cacheKey);
                if (!is_null($cached)) {
                    return $cached;
                }
                # 真正构建缓存
                $data = $this->getAllCacheData();# 重用基础方法
                Cache::put($cacheKey, $data, self::DEFAULT_TTL + rand(-300, 300));
                return $data;
            }
            # 未能获取锁，短暂等待后返回可能已构建的缓存或空数组
            usleep(50000);# 50ms
            return Cache::get($cacheKey, []);
        } catch (Throwable $e) {
            # 记录日志，降级直接查询数据库
            Log::error('菜单缓存获取失败', ['error' => $e->getMessage()]);
            return self::select(['id', 'title', /* 必要字段 */])->get()->toArray() ?: [];
        } finally {
            # 确保只有成功获取锁后才释放
            if ($lock && $lock->owned()) {
                $lock->release();
            }
        }
    }

    /**
     * 生成统一的缓存键
     */
    private function generateCacheKey(string $suffix): string
    {
        # 使用更简洁、唯一的方式生成键
        # 示例：antmin:menu:all 或 antmin:menu:tree
        return self::CACHE_PREFIX . $suffix;
    }
}
