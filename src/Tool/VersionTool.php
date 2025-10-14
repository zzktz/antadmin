<?php
/**
 * 版本更新控制
 */

namespace Antmin\Tool;

use Antmin\Third\VersionThird;


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


}
