<?php
/**
 * 请求日志管理
 */

namespace Antmin\Http\Services;


use App\Http\Tool\LogRequestTool;
use App\Common\Base;
use Antmin\Http\Repositories\RequestLogRepository;

class RequestLogService
{
    /**
     * 请求日志 列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getList(int $limit, array $search = []): array
    {
        $res = RequestLogRepository::getList($limit, $search);
        if (empty($res['data'])) {
            return $res;
        }
        $rest = [];
        foreach ($res['data'] as $k => $v) {
            $rest[$k]            = $v;
            $rest[$k]['app_env'] = $v['app_env'] == 'dev' ? Base::tag($v['app_env'], 'orange') : Base::tag($v['app_env'], 'green');
            $rest[$k]['method']  = $v['method'] == 'GET' ? Base::tag($v['method'], 'blue') : Base::tag($v['method'], 'green');
            if ($v['response_status'] > 200 && $v['response_status'] < 500) {
                $rest[$k]['response_status'] = Base::tag($v['response_status'], '#108ee9');
            } else if ($v['response_status'] == 200) {
                $rest[$k]['response_status'] = Base::tag($v['response_status'], 'green');
            } else {
                $rest[$k]['response_status'] = Base::tag($v['response_status'], '#f50');
            }
            if ($v['client'] == 'mini') {
                $rest[$k]['client'] = Base::tag($v['client'], 'green');
            } else if ($v['client'] == 'storeconsole') {
                $rest[$k]['client'] = Base::tag($v['client'], 'blue');
            } else if ($v['client'] == 'adminconsole') {
                $rest[$k]['client'] = Base::tag($v['client'], 'blue');
            }
            if ($v['client'] == 'mini') { # 显示系统和版本
                $rest[$k]['systemType'] = Base::tag($v['systemType'], '');
                if ($v['envVersion'] == 'develop') {
                    $rest[$k]['envVersion'] = Base::tag($v['envVersion'], 'orange');
                } else if ($v['envVersion'] == 'release') {
                    $rest[$k]['envVersion'] = Base::tag($v['envVersion'], 'green');
                } else {
                    $rest[$k]['envVersion'] = Base::tag($v['envVersion'], 'blue');
                }
            } else {
                $rest[$k]['systemType'] = '';
                $rest[$k]['envVersion'] = '';
            }
            # $rest[$k]['executionTime'] = Base::tag($v['executionTime'], 'green');
            $rest[$k]['action'] = $v['action'] ? Base::tag($v['action'], '') : '';
            $rest[$k]['id']     = $v['uuid'];
            $isSql              = LogRequestTool::getSqlQueryLog($v['uuid']);
            if (!empty($isSql)) {
                $rest[$k]['is_sql'] = Base::tag('有', 'red');
            } else {
                $rest[$k]['is_sql'] = Base::tag('无', 'green');
            }
            $rest[$k]['sqlres']        = LogRequestTool::getSqlQueryLog($v['uuid']);
            $rest[$k]['is_expand']     = false;
            $rest[$k]['content']       = $v['response_content'] ? json_decode($v['response_content'], true) : [];
            $rest[$k]['paramsarr']     = $v['params'] ? json_decode($v['params'], true) : [];
            $rest[$k]['executionTime'] = !empty($rest[$k]['content']['useTime']) ? Base::tag($rest[$k]['content']['useTime'], 'green') : '';

        }
        unset($res['data']);
        $res['data'] = $rest;
        return $res;
    }

    /**
     * 清空
     * @return mixed
     */
    public static function clear()
    {
        return RequestLogRepository::clearData();
    }
}
