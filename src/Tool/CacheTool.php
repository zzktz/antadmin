<?php

namespace Antmin\Tool;

use Antmin\Exceptions\CommonException;
use Illuminate\Support\Facades\Redis;
use Exception;
use Config;

class CacheTool
{

    protected static string $connection = 'default';


    /**
     * 清除指定Redis数据库的数据
     * @return void
     */
    public static function clearDatabase()
    {
        $redis = Redis::connection(self::$connection);

        $databases = [0, 1, 2, 3, 4, 13];

        foreach ($databases as $db) {
            $redis->select($db);
            $redis->flushdb();
        }
    }

    /**
     * 设置
     * @param string $key
     * @param array $data
     * @param int $outTime
     * @param string $connection
     * @return bool
     */
    public static function setArrCache(string $key, array $data, int $outTime = 0, string $connection = ''): bool
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $str        = json_encode($data);
            $redis      = Redis::connection($connection);
            if ($outTime < 1) {
                $redis->set($key, $str);
            } else {
                $redis->setex($key, $outTime, $str);
            }
            return true;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 获取
     * @param string $key
     * @param string $connection
     * @return array
     */
    public static function getArrCache(string $key, string $connection = ''): array
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            if ($redis->exists($key) != 1) {
                return [];
            }
            $str = $redis->get($key);
            if (!empty($str)) {
                $arr = json_decode($str, true);
            }
            return $arr ?? [];
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 删除
     * @param string $key
     * @param string $connection
     * @return bool
     */
    public static function delArrCache(string $key, string $connection = ''): bool
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            $redis->del($key);
            return true;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 判断是否存在
     * @param string $key
     * @param string $connection
     * @return bool
     */
    public static function isKeyExists(string $key, string $connection = ''): bool
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            return (bool)$redis->exists($key);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 加锁
     * @param string $key
     * @param string $value 每秒产生一个随机值
     * @param string $connection
     * @param int $ttl 过期时间  默认最低值 1 秒
     * @return bool
     */
    public static function lockGet(string $key, string $value, int $ttl = 1, string $connection = ''): bool
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            $res        = $redis->setnx($key, $value);
            if ($res) {
                $redis->expire($key, $ttl);
            }
            return (bool)$res;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 删除锁
     * @param string $key
     * @param string $value
     * @param string $connection
     * @return false
     */
    public static function lockDel(string $key, string $value, string $connection = ''): bool
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            if ($redis->get($key) == $value) {
                $redis->del($key);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 清空指定头的缓存
     * @param string $prefix
     * @param string $connection
     * @return int
     */
    public static function clearPrefix(string $prefix, string $connection = ''): int
    {
        try {
            $connection = !empty($connection) ? $connection : self::$connection;
            $redis      = Redis::connection($connection);
            $keys       = $redis->keys($prefix . '*');
            $prefix     = Config::get('database.redis.options.prefix');
            $resarr     = array_map(function ($value) use ($prefix) {
                return str_replace($prefix, "", $value);
            }, $keys);
            if (!empty($keys)) {
                return $redis->del($resarr);
            }
            return 0;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    /**
     * 获取类的字符串
     * @param string $fix
     * @param string $selfClass
     * @return string
     */
    public static function getPrefix(string $fix, string $selfClass):string
    {
        return str_replace(['\\', '.'], '_', $selfClass) . '_' . $fix;
    }

}
