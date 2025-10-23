<?php
/**
 * 操作日志
 */

namespace Antmin\Http\Services;

use App\Common\Base;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Repositories\OperateLogRepository;

class OperateLogService
{

    /**
     * 列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getList(int $limit, array $search): array
    {
        if (isset($search['date_arr']) && $search['date_arr']) {
            $search['start_at'] = !empty($search['date_arr']) ? reset($search['date_arr']) : '';
            $search['end_at']   = !empty($search['date_arr']) ? end($search['date_arr']) : '';
        }
        $res = OperateLogRepository::getList($limit, $search);
        if (empty($res['data'])) {
            return $res;
        }
        $rest = [];
        foreach ($res['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['operate']      = $v['operate'];
            $rest[$k]['action']       = $v['action'];
            $rest[$k]['content']      = $v['content'];
            $rest[$k]['account_name'] = !empty($v['account']['nickname']) ? $v['account']['nickname'] : '';
            $rest[$k]['created_at']   = $v['created_at'];
        }
        unset($res['data']);
        $res['data'] = $rest;
        return $res;
    }

    /**
     * 添加
     * @param string $operate
     * @param string $action
     * @param int $accountId
     * @param string $content
     */
    public static function add(string $operate, string $action, string $content = '')
    {
        $accountId         = request()['accountId'];
        $accountInfo       = AccountService::getAccountBaseInfo($accountId);
        $accountName       = $accountInfo['username'];
        $operate           = Base::utf8Substr($operate, 50, 0);
        $action            = Base::utf8Substr($action, 50, 0);
        $add['operate']    = $operate;
        $add['action']     = $action;
        $add['account_id'] = $accountId;
        $add['content']    = $accountName . $content;
        OperateLogRepository::add($add);
    }


}
