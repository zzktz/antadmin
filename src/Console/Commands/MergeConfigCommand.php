<?php

namespace Antmin\Console\Commands;

use Illuminate\Console\Command;
use Antmin\Providers\ServiceProvider;
use Illuminate\Support\Facades\Log;

class MergeConfigCommand extends Command
{
    /**
     * 命令名称和签名
     */
    protected $signature = 'antmin:merge-config  
                            {--force : 强制覆盖现有配置文件}
                            {--dry-run : 预览模式，只显示将要执行的操作但不实际执行}';

    /**
     * 命令描述
     */
    protected $description = '智能合并 Antmin 包配置到 Laravel 项目';

    /**
     * 包配置文件路径
     */
    protected string $packageConfigPath;

    /**
     * 目标配置文件路径
     */
    protected string $targetConfigPath;

    public function __construct()
    {
        parent::__construct();

        // 初始化路径
        $this->packageConfigPath = realpath(__DIR__ . '/../../../config/antmin.php');
        $this->targetConfigPath = config_path('antmin.php');

        Log::info('MergeConfigCommand 初始化完成', [
            'package_config' => $this->packageConfigPath,
            'target_config' => $this->targetConfigPath
        ]);
    }

    /**
     * 执行命令
     */
    public function handle(): int
    {
        Log::info('开始执行配置合并命令');

        $this->info('🚀 开始智能合并 Antmin 配置...');
        $this->line('');

        // 验证包配置文件是否存在
        if (!$this->validatePackageConfig()) {
            return self::FAILURE;
        }

        // 检查目标配置文件状态
        $configStatus = $this->checkConfigStatus();

        // 预览模式
        if ($this->option('dry-run')) {
            return $this->dryRun($configStatus);
        }

        // 执行实际合并
        return $this->executeMerge($configStatus);
    }

    /**
     * 验证包配置文件
     */
    protected function validatePackageConfig(): bool
    {
        if (!file_exists($this->packageConfigPath)) {
            $this->error("❌ 包配置文件不存在: {$this->packageConfigPath}");
            Log::error('包配置文件不存在', ['path' => $this->packageConfigPath]);
            return false;
        }

        if (!is_readable($this->packageConfigPath)) {
            $this->error("❌ 包配置文件不可读: {$this->packageConfigPath}");
            Log::error('包配置文件不可读', ['path' => $this->packageConfigPath]);
            return false;
        }

        $this->info("📦 包配置文件: {$this->packageConfigPath}");
        return true;
    }

    /**
     * 检查配置文件状态
     */
    protected function checkConfigStatus(): array
    {
        $status = [
            'target_exists' => file_exists($this->targetConfigPath),
            'target_writable' => is_writable(dirname($this->targetConfigPath)),
            'content_identical' => false,
        ];

        if ($status['target_exists']) {
            $packageContent = file_get_contents($this->packageConfigPath);
            $targetContent = file_get_contents($this->targetConfigPath);
            $status['content_identical'] = $packageContent === $targetContent;
        }

        return $status;
    }

    /**
     * 预览模式执行
     */
    protected function dryRun(array $status): int
    {
        $this->info('🔍 预览模式 - 以下是将要执行的操作:');
        $this->line('');

        if (!$status['target_exists']) {
            $this->line("✅ 将创建配置文件: {$this->targetConfigPath}");
        } elseif ($status['content_identical']) {
            $this->line("ℹ️  配置文件已是最新版本，无需更新: {$this->targetConfigPath}");
        } else {
            if ($this->option('force')) {
                $this->line("⚠️  将强制覆盖配置文件: {$this->targetConfigPath}");
            } else {
                $this->line("❌ 配置文件已存在且内容不同，使用 --force 选项强制覆盖: {$this->targetConfigPath}");
            }
        }

        $this->line('');
        $this->info('💡 使用不带 --dry-run 选项的命令来实际执行上述操作');

        return self::SUCCESS;
    }

    /**
     * 执行实际合并操作
     */
    protected function executeMerge(array $status): int
    {
        // 检查目标目录是否可写
        if (!$status['target_writable']) {
            $this->error("❌ 配置目录不可写: " . dirname($this->targetConfigPath));
            $this->line("💡 请执行: chmod 755 " . dirname($this->targetConfigPath));
            return self::FAILURE;
        }

        // 配置文件已存在且内容相同
        if ($status['target_exists'] && $status['content_identical']) {
            $this->info("✅ 配置文件已是最新版本: {$this->targetConfigPath}");
            Log::info('配置文件已是最新版本，无需更新');
            return self::SUCCESS;
        }

        // 配置文件已存在但内容不同
        if ($status['target_exists'] && !$status['content_identical']) {
            if (!$this->option('force')) {
                $this->error("❌ 配置文件已存在且内容不同: {$this->targetConfigPath}");
                $this->line("💡 使用 --force 选项强制覆盖现有配置");
                $this->line("💡 或者使用 --dry-run 选项预览差异");
                return self::FAILURE;
            }

            // 备份原有配置
            $backupPath = $this->targetConfigPath . '.backup.' . date('YmdHis');
            if (copy($this->targetConfigPath, $backupPath)) {
                $this->warn("📦 已备份原配置文件: {$backupPath}");
                Log::info('原配置文件已备份', ['backup_path' => $backupPath]);
            }
        }

        // 执行配置合并
        try {
            $result = ServiceProvider::mergeConfigFile($this->packageConfigPath, $this->targetConfigPath);

            if ($result) {
                $this->info("✅ 配置合并成功!");
                $this->line("📁 配置文件: {$this->targetConfigPath}");

                if ($status['target_exists'] && !$status['content_identical']) {
                    $this->warn("⚠️  注意: 原有配置已被覆盖，备份文件已创建");
                }

                Log::info('配置合并成功', [
                    'target_path' => $this->targetConfigPath,
                    'force_used' => $this->option('force')
                ]);

                return self::SUCCESS;
            } else {
                throw new \Exception('mergeConfigFile 返回 false');
            }

        } catch (\Exception $e) {
            $this->error("❌ 配置合并失败: " . $e->getMessage());
            Log::error('配置合并失败', [
                'error' => $e->getMessage(),
                'target_path' => $this->targetConfigPath
            ]);
            return self::FAILURE;
        }
    }


}