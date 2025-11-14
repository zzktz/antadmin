<?php

namespace Antmin\Http\Repositories;


use Antmin\Exceptions\CommonException;
use App\Jobs\LogRequestJob;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use Exception;

class RequestLogQueue
{


    public static string $queueName = 'log_request';


    public static function getList(int $limit, $search = []): array
    {
        return RequestLogRepository::getList($limit, $search);
    }


    public static function addStorage(array $data): void
    {
        # 加入队列
        try {
            LogRequestJob::dispatch($data)->onQueue(self::$queueName);
        } catch (Exception $e) {
            info($e->getMessage(), $e->getTrace());
            return;
        }
    }


    /**
     * 获取 队列长度
     * @return int
     */
    public static function getQueueLength(): int
    {
        $queueName    = self::$queueName;
        $queueStorage = config('antmin.logStorage');
        switch ($queueStorage) {
            case 'redis':
                return Redis::llen('queues:' . $queueName);
            case 'rabbitmq':
                return self::getRabbitMQQueueCount($queueName);
            default:
                return 0;
        }
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
        $host     = env('RABBITMQ_HOST', '');
        $port     = env('RABBITMQ_PORT', '');
        $user     = env('RABBITMQ_USER', '');
        $password = env('RABBITMQ_PASSWORD', '');

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
