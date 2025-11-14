<?php

namespace Antmin\Console\Commands;

use Illuminate\Console\Command;
use Antmin\Providers\ServiceProvider;

class MergeConfigCommand extends Command
{
    /**
     * 命令名称和签名
     */
    protected $signature = 'antmin:merge-config  {--force : 强制合并，即使配置相同也更新}';

    /**
     * 命令描述
     */
    protected $description = '智能合并 Antmin 包配置';

    /**
     * 执行命令
     */
    public function handle()
    {
        $packageConfigPath = __DIR__ . '/../../../config/antmin.php';
        $targetConfigPath  = config_path('antmin.php');

        \Log::info('------执行了-开始智能合并 Antmin 配置---');
        \Log::info($packageConfigPath);
        \Log::info($targetConfigPath);


        $this->info('开始智能合并 Antmin 配置...');

        if (ServiceProvider::mergeConfigFile($packageConfigPath, $targetConfigPath)) {
            $this->info('✅ 配置合并成功！');
            $this->info('📁 配置文件位置: ' . $targetConfigPath);
        } else {
            $this->error('❌ 配置合并失败！');
        }
    }
}