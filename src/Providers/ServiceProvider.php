<?php

namespace Antmin\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Antmin\Middleware\Middleware;
class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        // 在这里进行依赖注入绑定
        // 例如：$this->app->bind('your-package', fn() => new YourPackage);
    }

    public function boot()
    {
        // 在这里发布资源
        // 例如：加载路由、发布数据库迁移、发布配置文件等
//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__.'/../config/your-package.php' => config_path('your-package.php'),
//            ], 'your-package-config');
//        }

        $this->loadRoutesFrom(__DIR__ . '/../Config/Route.php');
        // 注册包的中间件
        $this->app['router']->aliasMiddleware('antAuth', Middleware::class);
        //$this->loadMigrationsFrom(__DIR__.'/../database/migrations'); // 加载迁移
    }
}