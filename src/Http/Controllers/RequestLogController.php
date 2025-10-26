<?php
/**
 * 请求日志
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
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
        $action = Base::getValue($request, 'action', '', 'required');
        if (method_exists(self::class, $action)) return self::$action($request);
        return errJson('No find action');
    }

    /**
     * 列表
     * @param  $request
     * @return mixed
     */
    protected static function index($request)
    {
        $limit  = Base::getValue($request, 'pageSize', '', 'integer');
        $search = [];
        $limit  = $limit ?? 5;
        $data   = RequestLogService::getList($limit, $search);
        return sucJson('成功', $data);
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
        return sucJson('成功');
    }
}
