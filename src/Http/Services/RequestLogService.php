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
use Antmin\Http\Repositories\RequestLogRepository;

class RequestLogService
{

    /**
     * 过滤请求的 url 特征值
     */
    protected const URLS_TO_REMOVE = [
        'fileUpload', 'systemUploadEditor',
        'systemUploadOperate', 'requestLogOperate', 'systemLogsOperate',
    ];

    /**
     * 过滤请求 params 参数 key
     */
    protected const KEYS_TO_REMOVE = [
        'token', 'access-token', 'authorization', 'password', 'captcha', 'code', 'secret', 'envVersion', 'page', 'reqUuid'
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
        if (in_array($logStorage, ['rabbitmq', 'database'], true)) {
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
        if ($logStorage === 'rabbitmq') {
            RequestLogQueue::addStorage($data);
        } elseif ($logStorage === 'database') {
            RequestLogRepository::addStorage($data);
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
        if (in_array(config('antmin.logStorage'), ['rabbitmq', 'database'], true)) {
            RequestLogRepository::clearData();
            return;
        }
        RequestLogRedis::clearData();
    }


    /**
     * 准备日志数据
     */
    protected static function prepareLogData(array $arr): array
    {
        $params       = self::filterParams($arr['params'] ?? []);
        $queryLogJson = self::formatParams($arr['query_log'] ?? []);

        return [
            'uuid'             => $arr['uuid'] ?? '',
            'app_env'          => (string) config('app.env'),
            'app_name'         => (string) config('app.name'),
            'url'              => self::getRequestUrl($arr),
            'client'           => $arr['client'] ?? '',
            'method'           => $arr['method'] ?? '',
            'header'           => self::redact($arr['header'] ?? []),
            'params'           => $params,
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
        $url = $arr['url'] ?? '';
        $str = parse_url($url) ?: [];
        return $str['path'] ?? '';
    }

    /**
     * 格式化参数
     */
    protected static function formatParams(array $params): string
    {
        $json = json_encode(self::redact($params), JSON_UNESCAPED_UNICODE) ?: '{}';
        return mb_substr($json, 0, 2000);
    }

    /**
     * 获取响应内容（只在成功时返回）
     */
    protected static function getResponseContent(array $arr): string
    {
        $statusCode = $arr['response_status'] ?? 0;
        $response   = $arr['response_content'] ?? '';

        if ((int) $statusCode < 200 || (int) $statusCode >= 300 || $response === '') {
            return '';
        }
        $decoded = json_decode($response, true);
        $safe = is_array($decoded) ? json_encode(self::redact($decoded), JSON_UNESCAPED_UNICODE) : $response;
        return mb_substr((string) $safe, 0, 2000);
    }

    /**
     * 处理统计
     */
    protected static function handleStatistics(array $arr): void
    {
        $memberId = is_array($arr['params'] ?? null) ? ($arr['params']['memberId'] ?? 0) : 0;
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
        return self::redact($param);
    }

    /**
     * 递归脱敏请求数据，避免密码、Token 等凭证进入日志。
     */
    protected static function redact($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        $sensitive = array_flip(array_map('strtolower', self::KEYS_TO_REMOVE));
        $result = [];
        foreach ($value as $key => $item) {
            if (isset($sensitive[strtolower((string) $key)])) {
                $result[$key] = '[已脱敏]';
            } else {
                $result[$key] = is_array($item) ? self::redact($item) : $item;
            }
        }
        return $result;
    }

    /**
     * 过滤url
     */
    protected static function isFilterUrl(string $url): bool
    {
        if (empty($url)) return false;
        return !empty(array_filter(self::URLS_TO_REMOVE, function ($item) use ($url) {
            return str_contains($url, $item);
        }));
    }


}
