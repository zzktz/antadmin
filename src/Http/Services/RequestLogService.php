<?php
/**
 * 请求日志管理
 */

namespace Antmin\Http\Services;

use Antmin\Tool\StatTool;
use Antmin\Tool\MemberTool;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\RequestLogRedis;
use Antmin\Http\Repositories\RequestLogQueue;

class RequestLogService
{

    /**
     * 过滤请求的 url 特征值
     * @var array|string[]
     */
    protected static array $urlsToRemove = [
        'fileUpload', 'systemUploadEditor',
        'systemUploadOperate', 'requestLogOperate', 'systemLogsOperate',
    ];

    /**
     * 过滤请求 params 参数 key
     * @var array|string[]
     */
    protected static array $keysToRemove = [
        'token', 'ReqClient', 'action', 'systemType',
        'envVersion', 'page', 'requestUuid', 'openid', 'deviceId'
    ];


    /**
     * 【请求日志】 列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getList(int $limit, array $search = []): array
    {
        $logStorage = config('antmin.logStorage');
        if ($logStorage == 'rabbitmq') {
            return RequestLogQueue::getList($limit, $search);
        } else {
            return RequestLogRedis::getList($limit, $search);
        }
    }


    /**
     * 【请求日志】添加
     * @param array $arr
     * @return void
     */
    public static function add(array $arr): void
    {
        # 数据提取和预处理
        $data = self::prepareLogData($arr);

        # 统计处理
        self::handleStatistics($arr);

        # URL过滤检查
        if (self::shouldFilter($data['url'])) {
            return;
        }
        # 存储
        $logStorage = config('antmin.logStorage');
        if ($logStorage == 'rabbitmq') {
            RequestLogQueue::addStorage($data);
        } else {
            RequestLogRedis::addStorage($data);
        }

    }


    /**
     * 清空数据
     * @param int $accountId
     * @return void
     */
    public static function clear(int $accountId): void
    {
        if ($accountId !== 1) {
            throw new CommonException('非超级管理员无权操作');
        }
        RequestLogRedis::clearData();
    }


    /**
     * 准备日志数据
     */
    protected static function prepareLogData(array $arr): array
    {
        $params       = self::filterParams($arr['params'] ?? []);
        $paramJson    = self::formatParams($params);
        $queryLogJson = self::formatParams($arr['query_log']);

        return [
            'uuid'             => $arr['uuid'] ?? '',
            'app_env'          => env('APP_ENV'),
            'app_name'         => env('APP_NAME'),
            'url'              => self::getRequestUrl($arr),
            'client'           => $arr['client'] ?? '',
            'method'           => $arr['method'] ?? '',
            'action'           => $arr['params']['action'] ?? '',
            'systemType'       => $arr['params']['systemType'] ?? '',
            'envVersion'       => $arr['params']['envVersion'] ?? '',
            'header'           => $arr['header'] ?? '',
            'params'           => $paramJson,
            'query_log'        => $queryLogJson, # 添加查询日志到记录数据
            'response_status'  => $arr['response_status'] ?? 0,
            'response_content' => self::getResponseContent($arr),
            'request_at'       => now()->toDateTimeString(),
        ];
    }

    /**
     * 获取请求URL
     */
    protected static function getRequestUrl(array $arr): string
    {
        return removeUrls($arr['url'] ?? '');
    }

    /**
     * 格式化参数
     */
    protected static function formatParams(array $params): string
    {
        $json = json_encode($params, JSON_UNESCAPED_UNICODE);
        return mb_substr($json, 0, 2000);
    }

    /**
     * 获取响应内容（只在成功时返回）
     */
    protected static function getResponseContent(array $arr): string
    {
        $statusCode = $arr['response_status'] ?? 0;
        $response   = $arr['response_content'] ?? '';

        return $statusCode === 200 ? $response : '';
    }

    /**
     * 处理统计
     */
    protected static function handleStatistics(array $arr): void
    {
        $memberId = $arr['params']['memberId'] ?? 0;
        $key      = RequestLogRedis::getStatKey();

        StatTool::setEveryHourStat($key);
        StatTool::setEveryDayStat($key);
        StatTool::setEveryMonthStat($key);
        StatTool::setEveryYearStat($key);
        StatTool::setTotalStat($key);

        if (!empty($memberId)) {
            MemberTool::stat($memberId);
        }
    }

    /**
     * 判断是否应该过滤URL
     */
    protected static function shouldFilter(string $url): bool
    {
        return self::isFilterUrl($url);
    }


    /**
     * 过滤参数
     */
    protected static function filterParams(array $param): array
    {
        $keysToRemove = self::$keysToRemove;
        return array_diff_key($param, array_flip($keysToRemove));
    }

    /**
     * 过滤url
     */
    protected static function isFilterUrl(string $url): bool
    {
        if (empty($url)) return false;
        return !empty(array_filter(self::$urlsToRemove, function ($item) use ($url) {
            return strpos($url, $item) !== false;
        }));
    }


}
