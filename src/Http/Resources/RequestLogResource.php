<?php

namespace Antmin\Http\Resources;


use Antmin\Common\Base;
use Antmin\Http\Repositories\RequestLogRedis;

class RequestLogResource
{

    /**
     * 请求日志 列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getList(int $limit, array $search = []): array
    {
        $res = RequestLogRedis::getListData($limit, $search);
        if (empty($res['data'])) {
            return $res;
        }

        // 辅助函数，给状态码着色
        $colorResponseStatus = function ($status) {
            if ($status > 200 && $status < 500) {
                return Base::tag($status, '#108ee9');
            }
            if ($status == 200) {
                return Base::tag($status, 'green');
            }
            return Base::tag($status, '#f50');
        };

        // 辅助函数，给客户端着色
        $colorClient = function ($client) {
            switch ($client) {
                case 'mini':
                    return Base::tag($client, 'green');
                case 'storeconsole':
                case 'adminconsole':
                    return Base::tag($client, 'blue');
                default:
                    return '';
            }
        };

        // 辅助函数，app_env 着色
        $colorAppEnv = function ($env) {
            return $env === 'dev' ? Base::tag($env, 'orange') : Base::tag($env, 'green');
        };

        // 辅助函数，method 着色
        $colorMethod = function ($method) {
            return $method === 'GET' ? Base::tag($method, 'blue') : Base::tag($method, 'green');
        };

        // 辅助函数，envVersion 着色
        $colorEnvVersion = function ($version) {
            switch ($version) {
                case 'develop':
                    return Base::tag($version, 'orange');
                case 'release':
                    return Base::tag($version, 'green');
                default:
                    return Base::tag($version, 'blue');
            }
        };

        $rest = [];

        foreach ($res['data'] as $k => $v) {
            $queryLog = !empty($v['query_log']) ? json_decode($v['query_log'], true) : [];

            $client          = $colorClient($v['client'] ?? '');
            $app_env         = $colorAppEnv($v['app_env'] ?? '');
            $method          = $colorMethod($v['method'] ?? '');
            $response_status = $colorResponseStatus($v['response_status'] ?? 0);

            if (($v['client'] ?? '') == 'mini') {
                $systemType = Base::tag($v['systemType'] ?? '', '');
                $envVersion = $colorEnvVersion($v['envVersion'] ?? '');
            } else {
                $systemType = '';
                $envVersion = '';
            }

            $rest[$k] = [
                'id'              => $v['uuid'] ?? '',
                'url'             => $v['url'] ?? '',
                'header'          => $v['header'] ?? '',
                'client'          => $client,
                'app_env'         => $app_env,
                'method'          => $method,
                'response_status' => $response_status,
                'systemType'      => $systemType,
                'envVersion'      => $envVersion,
                'action'          => !empty($v['action']) ? Base::tag($v['action'], '') : '',
                'is_sql'          => !empty($queryLog) ? Base::tag('有') : Base::tag('无', 'green'),
                'sqlres'          => $queryLog,
                'is_expand'       => false,
                'content'         => !empty($v['response_content']) ? json_decode($v['response_content'], true) : [],
                'paramsarr'       => (!empty($v['params']) && is_string($v['params'])) ? json_decode($v['params'], true) : [],
                // 执行时间
                'executionTime'   => !empty($v['response_content']) ?
                    (json_decode($v['response_content'], true)['useTime1'] ?? '') : '',
            ];

            // 上面 'executionTime' 依赖 content，优化版为避免重复解析 content：
            if (!empty($rest[$k]['content']['useTime1'])) {
                $rest[$k]['executionTime'] = Base::tag($rest[$k]['content']['useTime1'], 'green');
            } else {
                $rest[$k]['executionTime'] = '';
            }
        }

        unset($res['data']);
        $res['data'] = $rest;
        return $res;
    }

}
