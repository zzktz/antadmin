<?php

namespace Antmin\Providers;

use Antmin\Middleware\Middleware;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        # 注册配置为单例
        $this->app->singleton('antmin.config', function () {
            return config('antmin.connections', []);
        });
    }

    public function boot()
    {
        # 声明配置文件是可发布的
        $this->publishes([
            __DIR__ . '/../../config/antmin.php' => config_path('antmin.php'),
        ], 'antmin-config');

        # Laravel 12 推荐的路由加载方式
        $this->app->booted(function () {
            $this->loadRoutes();
        });

        # 注册包的中间件 - Laravel 12 推荐方式
        $router = $this->app->make('router');
        $router->aliasMiddleware('antAuth', Middleware::class);
    }

    /**
     * 加载包路由（Laravel 12 适配）
     */
    protected function loadRoutes(): void
    {
        $routeFile = __DIR__ . '/../Config/Route.php';
        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }



}