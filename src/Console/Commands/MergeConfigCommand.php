<?php

namespace Antmin\Console\Commands;

use Illuminate\Console\Command;
use Antmin\Providers\ServiceProvider;

class MergeConfigCommand extends Command
{
    /**
     * 命令名称和签名
     */
    protected $signature = 'antmin:merge-config 
                            {--force : 强制合并，即使配置相同也更新}';

    /**
     * 命令描述
     */
    protected $description = '智能合并 Antmin 包配置';

    /**
     * 执行命令
     */
    public function handle()
    {

        $this->info('开始智能合并 Antmin 配置...');

        if (ServiceProvider::mergeConfigDirectly()) {
            $this->info('✅ 配置合并成功！');
        } else {
            $this->error('❌ 配置合并失败！');
        }
    }
}