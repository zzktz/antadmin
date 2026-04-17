<?php
/**
 * 入口
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\LoginService;
use Antmin\Http\Services\PermissionsService;
use Antmin\Http\Services\SmsService;


use Illuminate\Http\Request;

class EnterController extends BaseController
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountService        $accountService,
        protected LoginService          $loginService,
        protected SmsService            $smsService,
        protected PermissionsService    $permissionsService,

        protected MenuController        $menuController,
        protected AccountController     $accountController,
        protected RoleController        $roleController,
        protected PermissionsController $permissionsController

    )
    {
        # 依赖已通过容器自动注入
    }

    public function operate(Request $request)
    {
        $action = $request['action'];
        if (method_exists(self::class, $action)) return $this->$action($request);
        throw new CommonException('System Not Find Action');
    }


    /**
     * 短信码
     * @param $request
     * @return mixed
     */
    protected function getSmsCode($request)
    {
        $type   = Base::getValue($request, 'type', '', 'required|max:100');
        $mobile = Base::getValue($request, 'mobile', '', 'required|mobile');
        $ipAddr = $request->getClientIp();
        $this->smsService->sendSmsCode($mobile, $ipAddr, $type);
        return Base::sucJson('成功');
    }


    protected function logout()
    {
        return Base::sucJson('成功');
    }

    protected function step2Code()
    {
        return Base::sucJson('成功');
    }

    /**
     * 【个人信息】基础信息
     * @param $request
     * @return mixed
     */
    protected function getUserInfo($request)
    {
        $accountId   = $request['accountId'];
        $res         = $this->accountService->getAccountBaseInfo($accountId);
        $permissions = $this->permissionsService->handleGetPermissionByAccountId($accountId);
        $res['role'] = $permissions;
        return Base::sucJson('成功', $res);
    }


    /**
     * 菜单管理
     */
    public function getMenuNav($request)
    {
        return $this->menuController->getMenuNav($request);
    }

    public function menuList($request)
    {
        return $this->menuController->menuList($request);
    }

    public function menuAdd($request)
    {
        return $this->menuController->menuAdd($request);
    }

    public function menuEdit($request)
    {
        return $this->menuController->menuEdit($request);
    }

    public function menuDel($request)
    {
        return $this->menuController->menuDel($request);
    }

    public function menuEditListorder($request)
    {
        return $this->menuController->menuEditListorder($request);
    }

    public function menuEditIsShow($request)
    {
        return $this->menuController->menuEditIsShow($request);
    }

    public function menuEditIsHideChildren($request)
    {
        return $this->menuController->menuEditIsHideChildren($request);
    }


    /**
     * 账号管理 个人信息编辑
     */
    protected function personalInfoEdit($request)
    {
        return $this->accountController->personalInfoEdit($request);
    }


    protected function accountList($request)
    {
        return $this->accountController->accountList($request);
    }

    protected function accountAdd($request)
    {
        return $this->accountController->accountAdd($request);
    }


    protected function accountEdit($request)
    {
        return $this->accountController->accountEdit($request);
    }


    protected function accountEditStatus($request)
    {
        return $this->accountController->accountEditStatus($request);
    }


    protected function accountDel($request)
    {
        return $this->accountController->accountDel($request);
    }

    protected function reInitPassword($request)
    {
        return $this->accountController->reInitPassword($request);
    }

    /**
     * 角色管理
     */
    protected function roleList($request)
    {
        return $this->roleController->roleList($request);
    }


    protected function roleAdd($request)
    {
        return $this->roleController->roleAdd($request);
    }


    protected function roleEdit($request)
    {
        return $this->roleController->roleEdit($request);
    }

    protected function roleRuleEdit($request)
    {
        return $this->roleController->roleRuleEdit($request);
    }

    protected function roleEditStatus($request)
    {
        return $this->roleController->roleEditStatus($request);
    }


    protected function roleDel($request)
    {
        return $this->roleController->roleDel($request);
    }

    /**
     * 权限列表
     */
    protected function permissionsList($request)
    {
        return $this->permissionsController->permissionsList($request);
    }

    protected function permissionsTree()
    {
        return $this->permissionsController->permissionsTree();
    }

    protected function permissionsAdd($request)
    {
        return $this->permissionsController->permissionsAdd($request);
    }

    protected function permissionsEdit($request)
    {
        return $this->permissionsController->permissionsEdit($request);
    }

    protected function permissionsEditStatus($request)
    {
        return $this->permissionsController->permissionsEditStatus($request);
    }

    protected function permissionsDel($request)
    {
        return $this->permissionsController->permissionsDel($request);
    }

    /**
     * 系统设置
     * @return mixed
     */
    protected function systemSetMiniNav()
    {
        return SystemSetController::systemSetMiniNav();
    }

    protected function systemSetList()
    {
        return SystemSetController::systemSetList();
    }

    protected function systemSetAdd($request)
    {
        return SystemSetController::systemSetAdd($request);
    }

    protected function systemSetEdit($request)
    {
        return SystemSetController::systemSetEdit($request);
    }

    protected function systemSetDel($request)
    {
        return SystemSetController::systemSetDel($request);
    }

    protected function systemSetEditListorder($request)
    {
        return SystemSetController::systemSetEditListorder($request);
    }

    protected function systemSetEditIsShow($request)
    {
        return SystemSetController::systemSetEditIsShow($request);
    }


    protected static function systemSetOneDetailContent($request)
    {
        return SystemSetController::systemSetOneDetailContent($request);
    }

    protected function systemSetOneDetailConfig($request)
    {
        return SystemSetController::systemSetOneDetailConfig($request);
    }

    protected function systemSetOneDetailConfigAdd($request)
    {
        return SystemSetController::systemSetOneDetailConfigAdd($request);
    }

    protected function systemSetOneDetailConfigEdit($request)
    {
        return SystemSetController::systemSetOneDetailConfigEdit($request);
    }

    protected function systemSetOneDetailConfigDel($request)
    {
        return SystemSetController::systemSetOneDetailConfigDel($request);
    }

    protected function systemSetOneDetailConfigEditListorder($request)
    {
        return SystemSetController::systemSetOneDetailConfigEditListorder($request);
    }

    protected function systemSetOneDetailConfiEditTip($request)
    {
        return SystemSetController::systemSetOneDetailConfiEditTip($request);
    }

    protected function systemSetOneDetailConfigIsShowSwitch($request)
    {
        return SystemSetController::systemSetOneDetailConfigIsShowSwitch($request);
    }

    protected function systemSetOneDetailConfigIsRequiredSwitch($request)
    {
        return SystemSetController::systemSetOneDetailConfigIsRequiredSwitch($request);
    }


}
