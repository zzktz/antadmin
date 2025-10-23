<?php
/**
 *  请求日志 工具
 */

namespace Antmin\Tool;

use Config;
use Illuminate\Support\Facades\Redis;

class LogRequestTool
{

    protected static string $queueName       = 'log_request';
    protected static string $mysqlConnection = 'log';
    protected static string $tableName       = 'app_request_log';
    protected static string $redisConnection = 'default4';
    protected static array  $keywords        = [
        'fileUpload',
        'systemUploadEditor',
        'systemUploadOperate',
        'requestLogOperate',
        'systemLogsOperate',
    ];

    protected static function getStatKey(): string
    {
        return env('APP_ENV') . '_log_request_stat';
    }

    public static function isFilterUrl(string $url): bool
    {
        if (empty($url)) return false;
        return !empty(array_filter(self::$keywords, function ($item) use ($url) {
            return strpos($url, $item) !== false;
        }));
    }

    /**
     * 【日志内存储存】添加
     * @param array $arr
     * @return void
     */
    public static function add(array $arr): void
    {
        # 如果不开启debug 直接返回
        $client     = $arr['client'] ?? '';
        $action     = $arr['params']['action'] ?? '';
        $systemType = $arr['params']['systemType'] ?? '';
        $envVersion = $arr['params']['envVersion'] ?? '';

        unset($arr['params']['token']);
        unset($arr['params']['ReqClient']);
        unset($arr['params']['action']);
        unset($arr['params']['systemType']);
        unset($arr['params']['envVersion']);
        unset($arr['params']['page']);
        unset($arr['params']['requestUuid']);
        unset($arr['params']['openid']);
        unset($arr['params']['deviceId']);


        if ($client != 'mini') {
            unset($arr['params']['parentSenceStr']);
            unset($arr['params']['parentMemberId']);
            unset($arr['params']['memberId']);
        }

        $params     = json_encode($arr['params'], JSON_UNESCAPED_UNICODE);
        $params     = mb_substr($params, 0, 1000);
        $response   = $arr['response_content'] ?? '';
        $statusCode = $arr['response_status'] ?? 0;

        $data['uuid']             = $arr['uuid'];
        $data['app_env']          = env('APP_ENV');
        $data['app_name']         = env('APP_NAME');
        $data['url']              = removeUrls($arr['url']) ?? '';
        $data['client']           = $client;
        $data['method']           = $arr['method'] ?? '';
        $data['action']           = $action;
        $data['systemType']       = $systemType;
        $data['envVersion']       = $envVersion;
        $data['header']           = $arr['header'];
        $data['params']           = $params;
        $data['response_status']  = $statusCode;
        $data['response_content'] = $statusCode == 200 ? $response : '';
        $data['request_at']       = date('Y-m-d H:i:s');
        $data['executionTime']    = $arr['executionTime'];


        # 统计
        $key = self::getStatKey();
        StatTool::setEveryHourStat($key);
        StatTool::setEveryDayStat($key);
        StatTool::setEveryMonthStat($key);
        StatTool::setEveryYearStat($key);
        StatTool::setTotalStat($key);

        # 判断是否包含过滤url 如果包含 直接返回，不加入队列
        if (self::isFilterUrl($data['url'])) {
            return;
        }
        # 【日志内存储存】方案
        self::addRedisStorege($data);
    }

    /**
     * 【日志内存储存】写入
     * @param array $data
     * @return void
     */
    public static function addRedisStorege(array $data): void
    {
        $key = self::getStatKey() . '_store_' . date('m');
        Redis::lpush($key, json_encode($data));
    }

    /**
     * 【日志内存储存】读取
     * @param int $num
     * @return array
     */
    public static function getLogData(int $perPage = 10)
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
     * 【日志内存储存】删除
     * @param string $month
     * @return void
     */
    public static function delRedisStorege(string $month = '')
    {
        $month = !empty($month) ? $month : date('m');
        $key   = self::getStatKey() . '_store_' . $month;
        Redis::del($key);
    }

    /**
     * 占用空间
     * @param string $month
     * @return mixed
     */
    public static function getUsageSize(string $month = '')
    {
        $month = !empty($month) ? $month : date('m');
        $key   = self::getStatKey() . '_store_' . $month;
        # 确认键是否存在
        if (!Redis::exists($key)) {
            return "Key does not exist";
        }
        $fixKey    = Config::get('database.redis.options.prefix') . $key;
        $bytesSiza = Redis::rawCommand('MEMORY', 'USAGE', $fixKey);
        return number_format($bytesSiza / (1024 * 1024), 2) . ' MB';
    }

    /**
     * 保存 一个请求的 sql 日志
     * @param string $requestUuid
     * @param array $info
     * @return void
     */
    public static function setSqlQueryLog(string $requestUuid, array $info): void
    {
        $key   = 'sql_query_store_req_uuid:' . $requestUuid;
        $arr   = self::getSqlQueryLog($requestUuid);
        $arr[] = $info;
        CacheTool::setArrCache($key, $arr, 3600, self::$redisConnection);
    }

    /**
     *  读取 一个请求的 sql 日志
     * @param string $requestUuid
     * @return array
     */
    public static function getSqlQueryLog(string $requestUuid): array
    {
        $key = 'sql_query_store_req_uuid:' . $requestUuid;
        $res = CacheTool::getArrCache($key, self::$redisConnection);
        return !empty($res) ? $res : [];
    }

    /**
     * 获取 队列长度
     * @return int
     */
    public static function getQueueLength(): int
    {
        $queueName   = self::$queueName;
        $queueDriver = config('queue.default');
        switch ($queueDriver) {
            case 'redis':
                $redis = Redis::connection(self::$redisConnection);
                return $redis->llen('queues:' . $queueName);
            case 'rabbitmq':
                return self::getRabbitMQQueueCount($queueName);
            default:
                return 0;
        }
    }

    /**
     * 统计 当前月总量
     * @return int
     */
    public static function getCurrentMonthTotal(): int
    {
        $key = self::getStatKey();
        return StatTool::getEveryMonthStat($key);
    }

    /**
     * 统计 当前总量
     * @return int
     */
    public static function getAllTotal(): int
    {
        $key = self::getStatKey();
        return StatTool::getTotalStat($key);
    }

    /**
     * 统计 今日请求总量
     * @return int
     */
    public static function getTodayTotal(): int
    {
        $key = self::getStatKey();
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
        $key = self::getStatKey() . '_' . $client;
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
        $key = self::getStatKey() . '_' . $client;
        StatTool::setEveryDayStat($key);
    }

    /**
     * 统计 昨日请求量
     * @return int
     */
    public static function getYesTodayTotal(): int
    {
        $yestoday = date('Y-m-d', time() - 86400);
        $key      = self::getStatKey() . ':' . $yestoday;
        return StatTool::getEveryDayStat($key, false);
    }

    /**
     * 统计 小时 chart
     * @return array
     */
    public static function getHourChart(): array
    {
        $str = self::getStatKey();
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
        $str = self::getStatKey();
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
        $str = self::getStatKey();
        $arr = StatTool::getRecentMonths();
        foreach ($arr as $k => $v) {
            $key                   = $str . ':' . $v;
            $chartData[$k]['name'] = $v;
            $chartData[$k]['x']    = StatTool::getEveryMonthStat($key, 0);
        }
        return $chartData ?? [];
    }

    /**
     * 获取队列中 消息数量
     * @param $queueName
     * @return mixed
     * @throws CommonException
     */
    public static function getRabbitMQQueueCount($queueName)
    {
        # RabbitMQ 的连接配置
        $host     = config('queue.connections.rabbitmq.host');
        $port     = config('queue.connections.rabbitmq.port');
        $user     = config('queue.connections.rabbitmq.user');
        $password = config('queue.connections.rabbitmq.password');

        try {
            # 创建连接
            $connection = new AMQPStreamConnection($host, $port, $user, $password);
            # 创建频道
            $channel = $connection->channel();
            # 获取队列信息 $queue 队列名称 ；$messageCount 消息数量 ； $consumerCount 活动状态的消费者的数量
            # list($queue, $messageCount, $consumerCount) = $channel->queue_declare($queueName, false, true, false, false, false, null);
            list(, $messageCount) = $channel->queue_declare($queueName, false, true, false, false, false, null);
            # 关闭频道和连接
            $channel->close();
            $connection->close();
            return $messageCount; # 返回消息数量
        } catch (AMQPConnectionClosedException $e) {
            # 处理连接关闭异常
            # 这里可以记录日志或抛出自定义异常
            throw new CommonException('AMQP:' . $e->getMessage());
        } catch (Exception $e) {
            # 处理其他可能的异常
            # 记录日志或抛出自定义异常
            throw new CommonException($e->getMessage());
        }
    }

}
