<?php

namespace Antmin\Http\Repositories;

use Base;
use Antmin\Models\RequestLog as Model;
use Antmin\Http\Resources\RequestLogResource;
use Illuminate\Support\Facades\Redis;

class RequestLogRepository
{


    public static function getList($limit, $search = []): array
    {
        $query = Model::query();
        if (!empty($search)) {
            if (!empty($search['id'])) {
                $query->where('id', $search['id']);
            }
            if (!empty($search['app_env'])) {
                $query->where('app_env', $search['app_env']);
            }
            if (!empty($search['client'])) {
                $query->where('client', $search['client']);
            }
            if (!empty($search['start_at'])) {
                $query->where('created_at','>=', $search['start_at']);
                $query->where('created_at','<=', $search['end_at']);
            }
            if (!empty($search['response_status'])) {
                $query->where('response_status', $search['response_status']);
            }
        }
        $query->orderBy('id', 'desc');
        $datas = Base::listFormat($limit, $query);
        return RequestLogResource::getFormatList($datas);
    }

    public static function getListData($limit, $search = []): array
    {
        $arrData           = self::getLogData($limit);
        $arrData['memory'] = self::getUsageSize();
        return $arrData;
    }

    /**
     * 【日志内存储存】读取
     * @param int $perPage
     * @return array
     */
    public static function getLogData(int $perPage = 10): array
    {
        $page = request()['page'] ?? 1;
        $key  = self::getStatKey() . '_store_' . date('m');
        # 使用 Redis 获取 api_request_logs 列表的总长度
        $totalLogs = Redis::llen($key);
        # 计算偏移量
        $offset = ($page - 1) * $perPage;
        # 获取当前页的数据
        $arr = Redis::lrange($key, $offset, $offset + $perPage - 1);
        # 将 JSON 数据解码为数组
        $data = array_map(function ($item) {
            return json_decode($item, true); # 解析为关联数组
        }, $arr);
        # 计算总页数
        $totalPages        = ceil($totalLogs / $perPage);
        $res['pageSize']   = $perPage;
        $res['pageNo']     = $page;
        $res['totalCount'] = $totalLogs;
        $res['totalPage']  = $totalPages;
        $res['data']       = $data;
        return $res;
    }


    /**
     * 【日志内存储存】写入
     */
    public static function addRedisStorage(array $data): void
    {
        $key = self::getStatKey() . '_store_' . date('m');
        Redis::lpush($key, json_encode($data));
    }

    /**
     * 【日志内存储存】清空
     */
    public static function clearData(string $month = ''): void
    {
        $month = !empty($month) ? $month : date('m');
        $key   = self::getStatKey() . '_store_' . $month;
        Redis::del($key);
    }


    /**
     * 占用空间
     */
    protected static function getUsageSize(string $month = ''): string
    {
        $month = !empty($month) ? $month : date('m');
        $key   = self::getStatKey() . '_store_' . $month;
        # 确认键是否存在
        if (!Redis::exists($key)) {
            return " 0 MB";
        }
        $fixKey    = config('database.redis.options.prefix') . $key;
        $bytesSiza = Redis::rawCommand('MEMORY', 'USAGE', $fixKey);
        return number_format($bytesSiza / (1024 * 1024), 2) . ' MB';
    }

    /**
     * key
     */
    public static function getStatKey(): string
    {
        return config('app.name') . '_log_request_stat';
    }


}
