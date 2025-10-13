<?php
/**
 * 角色
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\RoleService;


class RoleController extends BaseController
{


    /**
     * 角色列表
     * @param $request
     * @return mixed
     */
    public static function roleList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, 'pageSize', '', 'integer');
        $limit = $limit ?? 10;
        $res   = RoleService::roleList($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 角色添加
     * @param $request
     * @return mixed
     */
    public static function roleAdd($request)
    {
        $opId   = $request['accountId'];
        $vid    = Base::getValue($request, 'vid', '', 'required|letter|max:50');
        $name   = Base::getValue($request, 'name', '', 'required|max:50');
        $rules  = Base::getValue($request, 'rules', '', 'array');
        $roleId = RoleService::roleAdd($vid, $name, 1, $opId);
        RoleService::handleAddRolePermissions($rules, $roleId);
        return Base::sucJson('成功');
    }

    /**
     * 角色编辑
     * @param $request
     * @return mixed
     */
    public static function roleEdit($request)
    {
        $opId   = $request['accountId'];
        $roleId = Base::getValue($request, 'id', '', 'required|integer');
        $name   = Base::getValue($request, 'name', '', 'max:50');
        $rules  = Base::getValue($request, 'rules', '', 'array');
        $name   = $name ?? '';
        RoleService::edit($roleId, $name, $opId);
        # 先删除所有
        RoleService::handleDelRolePermissions($roleId);
        # 再重新添加
        RoleService::handleAddRolePermissions($rules, $roleId);
        return Base::sucJson('成功');
    }

    /**
     * 角色编辑状态
     * @param $request
     * @return mixed
     */
    public static function roleEditStatus($request)
    {
        $opId   = $request['accountId'];
        $roleId = Base::getValue($request, 'id', '', 'required|integer');
        RoleService::roleEditStatus($roleId, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 角色删除
     * @param $request
     * @return mixed
     */
    public static function roleDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        RoleService::roleDel($id, $opId);
        return Base::sucJson('成功');
    }

}