<?php
/**
 * 入口
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;

use Antmin\Http\Services\RoleService;


class RoleController extends BaseController
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected RoleService $roleService,
    )
    {
    }


    /**
     * 【角色管理】列表
     */
    public function roleList($request)
    {
        $limit = 99;
        $opId  = $request['accountId'];
        $res   = $this->roleService->index($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 【角色管理】添加
     */
    public function roleAdd($request)
    {
        $opId = $request['accountId'];
        $vid  = Base::getValue($request, 'vid', '', 'required|letter|max:50');
        $name = Base::getValue($request, 'name', '', 'required|max:50');
        $this->roleService->add($vid, $name, $opId);
        return Base::sucJson('添加成功');
    }

    /**
     * 【角色管理】编辑
     */
    public function roleEdit($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $name = Base::getValue($request, 'name', '', 'required|max:50');
        $this->roleService->edit(['name' => $name], $id, $opId);
        return Base::sucJson('编辑成功');
    }

    /**
     * 【角色管理】更改状态
     */
    public function roleEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->roleService->editStatus($id, $opId);
        return Base::sucJson('状态更新成功');
    }

    /**
     * 【角色管理】删除
     */
    public function roleDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->roleService->del($id, $opId);
        return Base::sucJson('删除成功');
    }


}

