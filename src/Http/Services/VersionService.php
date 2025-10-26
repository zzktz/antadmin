<?php
/**
 * 版本升级
 */

namespace Antmin\Http\Services;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Tool\VersionTool;
use Antmin\Third\VersionThird;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Exception;
use ZipArchive;
use Symfony\Component\Process\Process;

class VersionService
{


    /**
     * 项目ID
     * @return string
     */
    public static function getProjectId(): string
    {
        return config('app.project_id');
    }

    /**
     * 【主动升级】判断是否有新的版本 检查是否有新的版本 每次刷新请求
     * @param string $curVersionNo
     * @return int
     */
    public static function isHasNewVersion(string $curVersionNo): int
    {
        try {
            $projectId = self::getProjectId();
            if (empty($projectId) || empty($curVersionNo)) {
                return 0;
            }
            if (!Base::isVersionFormat($curVersionNo)) {
                return 0;
            }
            $latestVersion = self::getLatestVersion($curVersionNo);
            $versionNo     = $latestVersion['version_no'] ?? '0.0.0';
            $maxVersionNo  = Base::getMaxVersion($versionNo, $curVersionNo);
            if ($maxVersionNo == $curVersionNo) {
                return 0;
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }


    /**
     * 【主动升级】 vue前端 在控制服务器上进行版本更新  post
     * @param string $curVersion
     * @return bool
     */
    public static function updateVersion(string $curVersion):bool
    {
        $projectId  = self::getProjectId();
        $basePath   = base_path();
        $publicPath = public_path();
        $gitPath    = $basePath . '/.git';
        if (!file_exists($gitPath)) {
            throw new CommonException('后台仓库需要初始化');
        }
        if (empty($projectId)) {
            throw new CommonException('env文件中项目ID没有配置');
        }
        if (!Base::isVersionFormat($curVersion)) {
            throw new CommonException('当前版本号格式不正确');
        }
        # 获取下载文件地址
        $rest   = VersionThird::getLatestVersion($projectId, $curVersion);
        $zipUrl = $rest['zipUrl'] ?? '';
        $flag   = $rest['flag'] ?? '';

        if (empty($zipUrl)) {
            throw new CommonException('下载文件地址不存在');
        }
        if (empty($flag)) {
            throw new CommonException('项目flag不存在');
        }

        $distPath = $publicPath . '/dist/' . $flag;

        # 项目文件夹 请先确保nginx正确部署
        if (!file_exists($distPath)) {
            # 创建文件夹 动态创建 local2 磁盘配置
            $localDisk = Storage::build(['driver' => 'local', 'root' => $publicPath]);
            $localDisk->makeDirectory('dist/' . $flag);
        }
        info('项目开始更新');
        # 加锁
        $isLock = self::isHasLock();
        if ($isLock) {
            throw new CommonException('正在执行升级，不可重复执行');
        }

        # 获取下载文件信息
        $fileInfo  = pathinfo(parse_url($zipUrl, PHP_URL_PATH));
        $basename  = $fileInfo['basename'];  # 完整名称
        $filename  = $fileInfo['filename'];  # 扩展名
        $extension = $fileInfo['extension']; # 不含扩展名的文件名
        if ($extension != 'zip') {
            throw new CommonException('下载文件不是zip文件');
        }
        # 保存下载目录和解压目录
        $saveToPath  = storage_path('app/zip/');
        $extractPath = storage_path('app/zip/extr/');
        if (!file_exists($saveToPath)) {
            File::makeDirectory($saveToPath, 0777);
        }
        # 保存下载文件到指定路径
        info($filename . '版本ZIP压缩包开始下载');
        $saveTo = $saveToPath . $basename;
        VersionTool::download($zipUrl, $saveTo, $projectId);
        # 准备解压
        info('版本ZIP压缩包开始解压');
        $zip = new ZipArchive;
        if ($zip->open($saveTo) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        }
        # 清空旧的dist目录内容
        File::cleanDirectory($distPath);
        info('解压后文件开始复制');
        # 复制解压后的文件到指定运行目录
        $status = File::copyDirectory($extractPath, $distPath);
        if ($status) {
            # 清空临时解压目录
            File::cleanDirectory($saveToPath);
        }
        # 删除锁
        self::delLock();
        info('项目结束更新');
        return true;
    }

    /**
     * 【主动升级】 从控制服务器上获取最新版本信息 get
     * @param string $curVersion
     * @return array
     */
    public static function getLatestVersion(string $curVersion): array
    {
        $projectId = self::getProjectId();
        if (empty($projectId)) {
            throw new CommonException('项目ID不能为空');
        }
        if (!Base::isVersionFormat($curVersion)) {
            throw new CommonException('版本号格式不正确');
        }
        $rest              = VersionThird::getLatestVersion($projectId, $curVersion);
        $res['title']      = $rest['title'];
        $res['type']       = $rest['type'];
        $res['flag']       = $rest['flag'];
        $res['version_no'] = $rest['version_no'];
        $res['version_at'] = $rest['version_at'];
        $res['content']    = $rest['content'];
        $res['zipUrl']     = $rest['zipUrl'];
        $res['is_submit']  = $rest['is_submit'];
        return $res;
    }

    /**
     * 创建dist目前的软连接
     * @return true
     * @throws CommonException
     */
    public static function createDistSoftLink()
    {
        try {
            $basePath = base_path();
            $optDist  = ' /opt/public/dist';
            $appDist  = $basePath . '/public/dist';
            $commands = [
                'cd ' . $basePath,
                'ln -s ' . $optDist . ' ' . $appDist,
            ];
            # 将多个命令以分号连接成一个字符串
            $command = implode(' ; ', $commands);
            # 创建 Process 实例
            $process = Process::fromShellCommandline($command);
            # 执行
            $process->run();
            if (!$process->isSuccessful()) {
                throw new CommonException('【初始化】 命令执行失败' . $process->getErrorOutput());
            }
            # 获取命令的输出
            $process->getOutput();
            return true;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    protected static function isHasLock(): bool
    {
        $key = 'update_version_lock';
        if (empty(Redis::get($key))) {
            Redis::setex($key, 120, 1);
            return false;
        }
        return true;
    }

    protected static function delLock(): bool
    {
        $key = 'update_version_lock';
        Redis::del($key);
        return true;
    }

}
