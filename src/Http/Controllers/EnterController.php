<?php
/**
 * 入口
 */

namespace Antmin\Http\Controllers;

use Antmin\Exceptions\CommonException;
use Illuminate\Http\Request;

class EnterController extends BaseController
{


    public function operate(Request $request)
    {
        $action = $request['action'];
        unset($request['action']);
        if (method_exists(self::class, $action)) return self::$action($request);
        throw new CommonException('System Not Find Action');
    }


    protected static function getSmsCode($request)
    {
        return HomeController::getSmsCode($request);
    }

    protected static function logout()
    {
        return HomeController::logout();
    }

    protected static function step2Code()
    {
        return HomeController::step2Code();
    }

    protected static function getUserInfo($request)
    {
        return HomeController::getUserInfo($request);
    }

    protected static function personalInfoEdit($request)
    {
        return HomeController::personalInfoEdit($request);
    }

    protected static function reInitPassword($request)
    {
        return PersonSetController::reInitPassword($request);
    }

    /**
     * 左侧菜单
     * @return mixed
     */
    protected static function getMenuNav($request)
    {
        return MenuController::getMenuNav($request);
    }

    protected static function menuList($request)
    {
        return MenuController::menuList($request);
    }

    protected static function menuAdd($request)
    {
        return MenuController::menuAdd($request);
    }

    protected static function menuEdit($request)
    {
        return MenuController::menuEdit($request);
    }

    protected static function menuDel($request)
    {
        return MenuController::menuDel($request);
    }

    protected static function menuEditListorder($request)
    {
        return MenuController::menuEditListorder($request);
    }

    protected static function menuEditIsShow($request)
    {
        return MenuController::menuEditIsShow($request);
    }

    protected static function menuEditIsHideChildren($request)
    {
        return MenuController::menuEditIsHideChildren($request);
    }

    /**
     * 账号列表
     * @param $request
     * @return mixed
     */
    protected static function accountList($request)
    {
        return AccountController::accountList($request);
    }

    protected static function accountAdd($request)
    {
        return AccountController::accountAdd($request);
    }

    protected static function accountEdit($request)
    {
        return AccountController::accountEdit($request);
    }

    protected static function accountEditPassword($request)
    {
        return AccountController::accountEditPassword($request);
    }

    protected static function accountEditStatus($request)
    {
        return AccountController::accountEditStatus($request);
    }

    protected static function accountDetail($request)
    {
        return AccountController::accountDetail($request);
    }

    protected static function accountDel($request)
    {
        return AccountController::accountDel($request);
    }

    /**
     * 角色列表
     * @param $request
     * @return mixed
     */
    protected static function roleList($request)
    {
        return RoleController::roleList($request);
    }

    protected static function roleAdd($request)
    {
        return RoleController::roleAdd($request);
    }

    protected static function roleEdit($request)
    {
        return RoleController::roleEdit($request);
    }

    protected static function roleEditStatus($request)
    {
        return RoleController::roleEditStatus($request);
    }

    protected static function roleDel($request)
    {
        return RoleController::roleDel($request);
    }

    /**
     * 权限列表
     * @param $request
     * @return mixed
     */
    protected static function permissionsList($request)
    {
        return PermissionsController::permissionsList($request);
    }

    protected static function permissionsTree()
    {
        return PermissionsController::permissionsTree();
    }

    protected static function permissionsAdd($request)
    {
        return PermissionsController::permissionsAdd($request);
    }

    protected static function permissionsEdit($request)
    {
        return PermissionsController::permissionsEdit($request);
    }

    protected static function permissionsEditStatus($request)
    {
        return PermissionsController::permissionsEditStatus($request);
    }

    protected static function permissionsDel($request)
    {
        return PermissionsController::permissionsDel($request);
    }

    /**
     * 系统设置
     * @return mixed
     */
    protected static function systemSetMiniNav()
    {
        return SystemSetController::systemSetMiniNav();
    }

    protected static function systemSetList()
    {
        return SystemSetController::systemSetList();
    }

    protected static function systemSetAdd($request)
    {
        return SystemSetController::systemSetAdd($request);
    }

    protected static function systemSetEdit($request)
    {
        return SystemSetController::systemSetEdit($request);
    }

    protected static function systemSetDel($request)
    {
        return SystemSetController::systemSetDel($request);
    }

    protected static function systemSetEditListorder($request)
    {
        return SystemSetController::systemSetEditListorder($request);
    }

    protected static function systemSetEditIsShow($request)
    {
        return SystemSetController::systemSetEditIsShow($request);
    }


    protected static function systemSetOneDetailContent($request)
    {
        return SystemSetController::systemSetOneDetailContent($request);
    }

    protected static function systemSetOneDetailConfig($request)
    {
        return SystemSetController::systemSetOneDetailConfig($request);
    }

    protected static function systemSetOneDetailConfigAdd($request)
    {
        return SystemSetController::systemSetOneDetailConfigAdd($request);
    }

    protected static function systemSetOneDetailConfigEdit($request)
    {
        return SystemSetController::systemSetOneDetailConfigEdit($request);
    }

    protected static function systemSetOneDetailConfigDel($request)
    {
        return SystemSetController::systemSetOneDetailConfigDel($request);
    }

    protected static function systemSetOneDetailConfigEditListorder($request)
    {
        return SystemSetController::systemSetOneDetailConfigEditListorder($request);
    }

    protected static function systemSetOneDetailConfiEditTip($request)
    {
        return SystemSetController::systemSetOneDetailConfiEditTip($request);
    }

    protected static function systemSetOneDetailConfigIsShowSwitch($request)
    {
        return SystemSetController::systemSetOneDetailConfigIsShowSwitch($request);
    }

    protected static function systemSetOneDetailConfigIsRequiredSwitch($request)
    {
        return SystemSetController::systemSetOneDetailConfigIsRequiredSwitch($request);
    }


}
