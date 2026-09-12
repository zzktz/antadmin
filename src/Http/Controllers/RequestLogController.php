<?php
/**
 * 请求日志
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\RequestLogService;
use Illuminate\Http\Request;

class RequestLogController extends BaseController
{

    /**
     * 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        if ((int) ($request['accountId'] ?? 0) !== 1) {
            throw new CommonException('无权操作');
        }
        $action = Base::getValue($request, 'action', '', 'required');
        if (in_array($action, ['index', 'clear'], true) && is_callable([self::class, $action])) {
            return self::{$action}($request);
        }
        return Base::errJson('操作不存在');
    }

    /**
     * 列表
     * @param  $request
     * @return mixed
     */
    protected static function index($request)
    {
        $limit                     = Base::getValue($request, 'pageSize', '', 'integer');
        $search['id']              = Base::getValue($request, 'id', '', 'integer');
        $dateArr                   = Base::getValue($request, 'request_at', '', 'array') ?? [];
        $search['app_env']         = Base::getValue($request, 'app_env', '', '');
        $search['client']          = Base::getValue($request, 'client', '', '');
        $search['response_status'] = Base::getValue($request, 'response_status', '', '');
        if (!empty($dateArr)) {
            $search['start_at'] = reset($dateArr) . ' 00:00:00';
            $search['end_at']   = end($dateArr) . ' 23:59:59';
        }
        $limit = $limit ?? 5;
        $data  = RequestLogService::getList($limit, $search);
        return Base::sucJson('成功', $data);
    }

    /**
     * 清空
     * @param $request
     * @return mixed
     */
    protected static function clear($request)
    {
        $accountId = $request['accountId'];
        RequestLogService::clear($accountId);
        return Base::sucJson('成功');
    }
}
