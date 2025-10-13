<?php
/**
 * 权限规划
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\PermissionsService;

class PermissionsController extends BaseController
{


    /**
     * 权限列表
     * @param $request
     * @return mixed
     */
    public static function permissionsList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, "pageSize", '', 'integer');
        $limit = $limit ?? 10;
        $res   = PermissionsService::ruleList($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 权限树
     * @return mixed
     */
    public static function permissionsTree()
    {
        $res = PermissionsService::ruleListTree();
        return Base::sucJson('成功', $res);
    }

    /**
     * 权限添加
     * @param $request
     * @return mixed
     */
    public static function permissionsAdd($request)
    {
        $opId          = $request['accountId'];
        $add['vid']    = Base::getValue($request, 'vid', '', 'required|letter|max:30');
        $add['title']  = Base::getValue($request, 'title', '', 'required|max:50');
        $add['pid']    = Base::getValue($request, 'pid', '', 'required|integer');
        $add['status'] = 1;
        PermissionsService::ruleAdd($add, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 权限编辑
     * @param $request
     * @return mixed
     */
    public static function permissionsEdit($request)
    {
        $opId              = $request['accountId'];
        $id                = Base::getValue($request, 'id', '', 'required|integer');
        $up['vid']         = Base::getValue($request, 'vid', '', 'required|letter|max:30');
        $up['title']       = Base::getValue($request, 'title', '', 'required|max:50');
        $up['pid']         = Base::getValue($request, 'pid', '', 'integer');
        PermissionsService::ruleEdit($up, $id, $opId);
        return Base::sucJson('成功');
    }

    public static function permissionsEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        PermissionsService::ruleEditStatus($id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 权限删除
     * @param $request
     * @return mixed
     */
    public static function permissionsDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        PermissionsService::ruleDel($id, $opId);
        return Base::sucJson('成功');
    }


}