<?php

namespace Antmin\Providers;

use Illuminate\Support\Facades\File;
use Antmin\Middleware\Middleware;
use Antmin\Console\Commands\MergeConfigCommand;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        \Log::info('------ServiceProvider register方法执行---');
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

        # 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../Config/Route.php');

        # 注册包的中间件
        $this->app['router']->aliasMiddleware('antAuth', Middleware::class);

        # 动态注册数据库连接
        $this->registerDatabaseConnections();

        # 注册自定义 Artisan 命令
        if ($this->app->runningInConsole()) {
            \Log::info('------ServiceProvider boot方法执行---');
            self::mergeConfigDirectly();
            \Log::info('------命令注册完成---');
        }

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

    /**
     * 直接合并配置（供 Composer 脚本调用）
     * 这个方法不依赖 Laravel 容器，可以在 Composer post-autoload-dump 时执行
     */
    public static function mergeConfigDirectly(): void
    {
        \Log::info('------mergeConfigDirectly000000 开始---');

        # 包内配置文件的路径
        $packageConfigPath = __DIR__ . '/../../config/antmin.php';

        # 目标配置文件路径（项目中的 config 目录）
        $targetConfigPath = getcwd() . '/config/antmin.php';

        # 检查包配置文件是否存在
        if (!file_exists($packageConfigPath)) {
            echo "⚠️ 包配置文件不存在: {$packageConfigPath}\n";
            return;
        }

        # 读取包中的默认配置
        $defaultConfig = include $packageConfigPath;

        # 如果目标文件不存在，直接复制
        if (!file_exists($targetConfigPath)) {
            if (copy($packageConfigPath, $targetConfigPath)) {
                echo "✅ Antmin 配置已创建: {$targetConfigPath}\n";
            } else {
                echo "❌ 无法创建配置文件: {$targetConfigPath}\n";
            }
            return;
        }

        # 读取现有的用户配置
        $userConfig = include $targetConfigPath;

        # 深度合并配置
        $mergedConfig = self::arrayMergeRecursiveDistinct($defaultConfig, $userConfig);

        # 生成新的配置文件内容
        $configContent = "<?php\n\nreturn " . var_export($mergedConfig, true) . ";\n";

        # 格式化 PHP 代码
        $configContent = self::formatConfigContent($configContent);

        # 写入合并后的配置
        if (file_put_contents($targetConfigPath, $configContent) !== false) {
            echo "✅ Antmin 配置已智能合并: {$targetConfigPath}\n";
        } else {
            echo "❌ 无法写入配置文件: {$targetConfigPath}\n";
        }
    }

    /**
     * 智能合并配置文件的实现（供 Artisan 命令使用）
     */
    public static function mergeConfigFile(string $packageConfigPath, string $targetConfigPath): bool
    {
        if (!File::exists($packageConfigPath)) {
            return false;
        }

        # 读取包中的默认配置
        $defaultConfig = require $packageConfigPath;

        # 如果目标文件不存在，直接复制
        if (!File::exists($targetConfigPath)) {
            return File::copy($packageConfigPath, $targetConfigPath);
        }

        # 读取现有的用户配置
        $userConfig = require $targetConfigPath;

        # 深度合并配置
        $mergedConfig = self::arrayMergeRecursiveDistinct($defaultConfig, $userConfig);

        # 生成新的配置文件内容
        $configContent = "<?php\n\nreturn " . var_export($mergedConfig, true) . ";\n";

        # 格式化 PHP 代码
        $configContent = self::formatConfigContent($configContent);

        # 写入合并后的配置
        return File::put($targetConfigPath, $configContent) !== false;
    }

    /**
     * 深度合并数组（保留用户配置的优先级）
     */
    private static function arrayMergeRecursiveDistinct(array $array1, array $array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * 格式化配置内容
     */
    private static function formatConfigContent(string $content): string
    {
        # 美化数组格式
        $content = preg_replace('/array \(/', '[', $content);
        $content = preg_replace('/\)/', ']', $content);
        $content = preg_replace('/=>\s*\[/', '=> [', $content);
        return preg_replace('/(\s+)\[/', "$1[", $content);
    }


}