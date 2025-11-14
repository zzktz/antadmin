<?php

namespace Antmin\Providers;


use Antmin\Middleware\Middleware;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {

        // 注册配置为单例
        $this->app->singleton('antmin.config', function () {
            return config('antmin.connections', []);
        });


    }

    public function boot()
    {


        # 声明配置文件是可发布的
        $this->publishes([
            __DIR__ . '/../config/antmin.php' => config_path('antmin.php'),
        ], 'antmin-config');

        # 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../Config/Route.php');

        # 注册包的中间件
        $this->app['router']->aliasMiddleware('antAuth', Middleware::class);

        # 动态注册数据库连接
        $this->registerDatabaseConnections();
    }

    /**
     * 注册数据库连接
     */
    protected function registerDatabaseConnections(): void
    {
        $connections = $this->app->make('antmin.config');
        foreach ($connections as $name => $config) {
            config(["database.connections.{$name}" => $config]);
        }
    }
}