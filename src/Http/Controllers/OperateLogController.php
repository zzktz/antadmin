<?php
/**
 * 操作日志
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\OperateLogService;
use Illuminate\Http\Request;


class OperateLogController extends BaseController
{


    public function __construct(
        protected OperateLogService $operateLogService,
    )
    {
    }


    public function operate(Request $request)
    {
        $action = $request['action'];
        if (method_exists(self::class, $action)) return $this->$action($request);
        throw new CommonException('System Not Find Action');
    }

    /**
     * 值班记录 列表
     * @param $request
     * @return mixed
     */
    public function index($request)
    {
        $limit                  = Base::getValue($request, 'pageSize', '', 'integer');
        $search['operate']      = Base::getValue($request, 'operate', '操作', 'max:99');
        $search['action']       = Base::getValue($request, 'type', '类型', 'max:99');
        $search['account_name'] = Base::getValue($request, 'account_name', '', '');
        $search['date_arr']     = Base::getValue($request, 'time', '', '');
        $limit                  = $limit ?? 10;
        $res                    = $this->operateLogService->getList($limit, $search);
        return sucJson('ok', $res);
    }

}
