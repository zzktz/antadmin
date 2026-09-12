<?php
/**
 * 菜单管理
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\MenuService;

class MenuController extends BaseController
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected MenuService $menuService,

    )
    {
    }


    /**
     * 【菜单管理】左侧菜单
     */
    public  function getMenuNav($request)
    {
        $opId = $request['accountId'];
        $res  = $this->menuService->getMenuNav($opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 【菜单管理】列表
     */
    public function menuList($request)
    {
        $parentId = Base::getValue($request, 'parentId', '', 'integer');
        $parentId = $parentId ?? 0;
        $res      = $this->menuService->menuList($parentId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 【菜单管理】 添加
     */
    public function menuAdd($request)
    {
        $opId                  = $request['accountId'];
        $info['parentId']      = Base::getValue($request, 'parentId', '', 'integer');
        $info['title']         = Base::getValue($request, 'title', '', 'required|max:100');
        $info['icon']          = Base::getValue($request, 'icon', '', 'max:100');
        $info['pageName']      = Base::getValue($request, 'pageName', '', 'required|max:100');
        $info['routePath']     = Base::getValue($request, 'routePath', '', 'required|max:100');
        $info['component']     = Base::getValue($request, 'component', '', 'required|max:100');
        $info['redirect']      = Base::getValue($request, 'redirect', '', 'max:200');
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array') ?? [];
        $this->menuService->menuAdd($info, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】编辑
     */
    public function menuEdit($request)
    {
        $opId                  = $request['accountId'];
        $id                    = Base::getValue($request, 'id', '', 'required|integer');
        $info['parentId']      = Base::getValue($request, 'parentId', '', 'integer');
        $info['title']         = Base::getValue($request, 'title', '', 'required|max:100');
        $info['icon']          = Base::getValue($request, 'icon', '', 'max:100');
        $info['pageName']      = Base::getValue($request, 'pageName', '', 'required|max:100');
        $info['routePath']     = Base::getValue($request, 'routePath', '', 'required|max:100');
        $info['component']     = Base::getValue($request, 'component', '', 'required|max:100');
        $info['redirect']      = Base::getValue($request, 'redirect', '', 'max:200');
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array') ?? [];
        $this->menuService->menuEdit($info, $id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】 删除
     */
    public function menuDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->menuService->menuDel($id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】排序
     */
    public function menuEditListorder($request)
    {
        $opId      = $request['accountId'];
        $id        = Base::getValue($request, 'id', '', 'required|integer');
        $listorder = Base::getValue($request, 'listorder', '', 'required|integer');
        $this->menuService->menuEditListorder($listorder, $id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】设置是否显示页面
     */
    public function menuEditIsShow($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->menuService->menuEditIsShow($id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】设置是否隐藏子菜单
     */
    public function menuEditIsHideChildren($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->menuService->menuEditIsHideChildren($id, $opId);
        return Base::sucJson('成功');
    }


}
