<?php
/**
 * 版本更新控制
 */

namespace Antmin\Tool;

use Antmin\Third\VersionThird;
use Antmin\Exceptions\CommonException;
use Symfony\Component\Process\Process;


class VersionTool
{

    /**
     * 下载文件 有下载进度
     * @param string $zipUrl
     * @param string $saveTo
     * @param string $appId
     * @return true
     */
    public static function download(string $zipUrl, string $saveTo, string $appId)
    {
        $lastDownloaded = 0; # 记录上一次的下载字节数
        $ch             = curl_init($zipUrl);
        # 设置 cURL 选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, $download_size, $downloaded, $upload_size, $uploaded) use ($saveTo, $appId, &$lastDownloaded) {
            if ($download_size > 0) {
                $progress = round(($downloaded / $download_size) * 100);
                if ($progress != 100 && $progress != 0 && $progress > $lastDownloaded) {
                    # info($progress);
                    VersionThird::sendDownProgress($appId, $progress);
                    # echo "下载进度：{$progress}%\n";
                    $lastDownloaded = $progress;
                    if ($progress == 99) {
                        VersionThird::sendDownProgress($appId, 100);
                    }
                }
            }
        });
        #  执行 cURL 请求
        $fileContent = curl_exec($ch);
        #  检查是否有错误发生
        if (curl_errno($ch)) {
            info('文件下载错误: ' . curl_error($ch));
        }
        #  关闭 cURL 资源，并释放系统资源
        curl_close($ch);
        #  将文件内容保存到本地
        file_put_contents($saveTo, $fileContent);
        return true;
    }





    /**
     * 仓库拉取
     * @return bool
     */
    public static function pull(): bool
    {
        $path    = base_path();
        $gitPath = $path . '/.git';
        # 是否已初始化
        if (!file_exists($gitPath)) {
            return false;
        }
        # 检查是否有新的版本命令
        $commands1 = [
            'cd ' . $path,
            'git config credential.helper store',
            'git fetch origin && [ `git rev-parse HEAD` = `git rev-parse origin/master` ] && echo "no_new" || echo "yes_new"',
        ];
        # 将多个命令以分号连接成一个字符串
        $command = implode(' ; ', $commands1);
        # 创建 Process 实例
        $process = Process::fromShellCommandline($command);
        # 执行命令
        $process->run();
        if (!$process->isSuccessful()) {
            info('后台执行fetch命令失败' . $process->getErrorOutput());
        }
        # 取命令输出
        $output = $process->getOutput();
        # 获取最后一行 有无更新标志
        $lines  = explode("\n", $output);
        $result = null;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (!empty($lines[$i])) {
                $result = $lines[$i];
                break;
            }
        }
        # 拉取明年
        $commands2 = [
            'cd ' . $path,
            'git pull origin master',
        ];
        if ($result == 'yes_new') {
            # 将多个命令以分号连接成一个字符串
            $command2 = implode(' ; ', $commands2);
            $process  = Process::fromShellCommandline($command2);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new CommonException('后台执行PULL命令失败' . $process->getErrorOutput());
            }
            info('后台PULL仓库成功：' . $result);
        } else {
            info('后台已经是新版本：' . $result);
        }
        return true;
    }


}
