<?php

namespace Antmin\Providers;


use Antmin\Middleware\Middleware;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        # 在这里进行依赖注入绑定
        # 例如：$this->app->bind('your-package', fn() => new YourPackage);
    }

    public function boot()
    {
        # 例如：加载路由、发布数据库迁移、发布配置文件等
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/antmin.php' => config_path('antmin.php'),
            ], 'antmin-config');
        }

        # 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../Config/Route.php');

        # 注册包的中间件
        $this->app['router']->aliasMiddleware('antAuth', Middleware::class);

    }


}