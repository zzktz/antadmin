<?php
/**
 *  版本升级请求
 */

namespace Antmin\Third;

use Antmin\Common\Limit;
use Antmin\Exceptions\CommonException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class VersionThird
{

    protected static string $apiUrl = 'https://www.zzktz.com/api/';

    /**
     * 获取最新版本信息
     * @param string $projectId
     * @param string $curVersion
     * @return array
     */
    public static function getLatestVersion(string $projectId, string $curVersion): array
    {
        $appUrl = self::$apiUrl . 'getVersionInfo';

        $params['projectId']  = $projectId;
        $params['curVersion'] = $curVersion;
        $resStr               = self::reqestUrl('POST', $appUrl, $params);
        $resArr               = json_decode($resStr, true);
        if ($resArr['status'] == 'fail') {
            throw new CommonException($resArr['message']);
        }
        return $resArr['data'];
    }

    /**
     * 发送进度
     * @param string $projectId
     * @param int $progress
     * @return void
     */
    public static function sendDownProgress(string $projectId, int $progress): void
    {
        $appUrl              = self::$apiUrl . 'sendDownLoadProgress';
        $params['projectId'] = $projectId;
        $params['progress']  = $progress;
        if ($progress % 10 == 0) {
            $key = 'SendDownLoadProgress_' . $projectId;
            $res = Limit::handle($key, 1, 1);# 最大1秒发送1次
            if ($res) {
                self::reqestUrl('POST', $appUrl, $params);
            }
        }
    }

    /**
     * 请求
     * @param string $method
     * @param string $url
     * @param array $data
     * @return string 返回字符串
     */
    private static function reqestUrl(string $method, string $url, array $data = []): string
    {
        $client = new Client(['timeout' => 60]);
        try {
            $str     = url('');
            $str     = preg_replace('#^https?://#', '', $str);
            $str     = rtrim($str, '/');
            $headers = ['Content-Type' => 'application/json', 'X-Custom-Referer' => $str];
            if ($method == 'GET') {
                $url = $url . '?' . http_build_query($data);
            }
            $options    = [
                'headers' => $headers,
                'body'    => json_encode($data)
            ];
            $response   = $client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            if ($statusCode != 200) {
                throw new CommonException('服务器故障，状态码：' . $statusCode);
            }
            return $response->getBody();
        } catch (RequestException $e) {
            throw new CommonException($e->getMessage());
        }
    }

}