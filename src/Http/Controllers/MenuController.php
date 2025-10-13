<?php
/**
 * 菜单
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\MenuService;

class MenuController extends BaseController
{


    /**
     * 左侧菜单
     * @return mixed
     */
    public static function getMenuNav($request)
    {
        $opId = $request['accountId'];
        $res  = MenuService::getMenuNav($opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 菜单列表
     * @param $request
     * @return mixed
     */
    public static function menuList($request)
    {
        $parentId = Base::getValue($request, 'parentId', '', 'integer');
        $parentId = $parentId ?? 0;
        $res      = MenuService::menuList($parentId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 菜单添加
     * @param $request
     * @return mixed
     */
    public static function menuAdd($request)
    {
        $opId                  = $request['accountId'];
        $info['parentId']      = Base::getValue($request, 'parentId', '', 'integer');
        $info['title']         = Base::getValue($request, 'title', '', 'required|max:100');
        $info['icon']          = Base::getValue($request, 'icon', '', 'max:100');
        $info['pageName']      = Base::getValue($request, 'pageName', '', 'required|max:100');
        $info['routePath']     = Base::getValue($request, 'routePath', '', 'required|max:100');
        $info['component']     = Base::getValue($request, 'component', '', 'required|max:100');
        $info['redirect']      = Base::getValue($request, 'redirect', '', 'max:200');
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array');
        MenuService::menuAdd($info, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 菜单编辑
     * @param $request
     * @return mixed
     */
    public static function menuEdit($request)
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
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array');
        MenuService::menuEdit($info, $id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 菜单删除
     * @param $request
     * @return mixed
     */
    public static function menuDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        MenuService::menuDel($id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 菜单编辑排序
     * @param $request
     * @return mixed
     */
    public static function menuEditListorder($request)
    {
        $opId      = $request['accountId'];
        $id        = Base::getValue($request, 'id', '', 'required|integer');
        $listorder = Base::getValue($request, 'listorder', '', 'required|integer');
        MenuService::menuEditListorder($listorder, $id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 菜单设置是否显示页面
     * @param $request
     * @return mixed
     */
    public static function menuEditIsShow($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        MenuService::menuEditIsShow($id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 菜单设置是否隐藏子菜单
     * @param $request
     * @return mixed
     */
    public static function menuEditIsHideChildren($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        MenuService::menuEditIsHideChildren($id, $opId);
        return Base::sucJson('成功');
    }


}