<?php

namespace Antmin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Antmin\Http\Repositories\RequestLogQueue;

class LogRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    # 尝试次数
    public int $tries = 3;
    # 最大超时
    public int $timeout = 10;

    protected array $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {

    }

    /**
     * 静态分发方法，包含连接配置
     */
    public static function dispatchWithCustomConnection(array $data): PendingDispatch
    {
        $connectionName = self::setupDynamicConnection();

        return self::dispatch($data)
            ->onConnection($connectionName)
            ->onQueue(RequestLogQueue::$queueName);
    }

    /**
     * 设置动态连接配置
     */
    protected static function setupDynamicConnection(): string
    {
        $connectionName = 'rabbitmq';

        $config = RequestLogQueue::getConfig();

        # RabbitMQ 的连接配置
        $host     = $config['host'];
        $port     = $config['port'];
        $user     = $config['user'];
        $password = $config['password'];


        if (!config("queue.connections" . $connectionName)) {
            config(["queue.connections" . $connectionName => [
                'driver'   => 'rabbitmq',
                'host'     => $host,
                'port'     => $port,
                'user'     => $user,
                'password' => $password,
                'options'  => [
                    'queue' => [
                        'queue' => RequestLogQueue::$queueName,
                    ],
                ],
            ]]);
        }

        return $connectionName;
    }


}

