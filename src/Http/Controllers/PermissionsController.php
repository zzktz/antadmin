<?php
/**
 * 权限
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\PermissionsService;


class PermissionsController extends BaseController
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected PermissionsService $permissionsService,
    )
    {
    }


    public function permissionsList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, "pageSize", '', 'integer');
        $limit = $limit ?? 10;
        $res   = $this->permissionsService->ruleList($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    public function permissionsTree()
    {
        $res = $this->permissionsService->ruleListTree();
        return Base::sucJson('成功', $res);
    }

    public function permissionsAdd($request)
    {
        $opId          = $request['accountId'];
        $add['vid']    = Base::getValue($request, 'vid', '', 'required|letter|max:30');
        $add['title']  = Base::getValue($request, 'title', '', 'required|max:50');
        $add['pid']    = Base::getValue($request, 'pid', '', 'required|integer');
        $add['status'] = 1;
        $this->permissionsService->ruleAdd($add, $opId);
        return Base::sucJson('成功');
    }

    public function permissionsEdit($request)
    {
        $opId        = $request['accountId'];
        $id          = Base::getValue($request, 'id', '', 'required|integer');
        $up['vid']   = Base::getValue($request, 'vid', '', 'required|letter|max:30');
        $up['title'] = Base::getValue($request, 'title', '', 'required|max:50');
        $up['pid']   = Base::getValue($request, 'pid', '', 'integer');
        $this->permissionsService->ruleEdit($up, $id, $opId);
        return Base::sucJson('成功');
    }

    public function permissionsEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->permissionsService->ruleEditStatus($id, $opId);
        return Base::sucJson('成功');
    }

    public function permissionsDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->permissionsService->ruleDel($id, $opId);
        return Base::sucJson('成功');
    }


}
