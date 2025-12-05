<?php
/**
 * 入口
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\LoginService;
use Antmin\Http\Services\MenuService;
use Antmin\Http\Services\PermissionsService;
use Antmin\Http\Services\SmsService;
use Antmin\Http\Services\RoleService;

use Illuminate\Http\Request;

class EnterController extends BaseController
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountService     $accountService,
        protected LoginService       $loginService,
        protected SmsService         $smsService,
        protected PermissionsService $permissionsService,
        protected MenuService        $menuService,
        protected RoleService        $roleService,
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
     * 【个人信息】编辑
     * @param $request
     * @return mixed
     */
    protected function personalInfoEdit($request)
    {
        $accountId = $request['accountId'];
        $email     = Base::getValue($request, 'email', '', 'email');
        $mobile    = Base::getValue($request, 'mobile', '', 'mobile');
        $nickname  = Base::getValue($request, 'nickname', '', 'alpha_dash|max:50');
        $birthday  = Base::getValue($request, 'birthday', '', 'date_format:Y-m-d');
        if (!empty($mobile)) {
            $filed = 'mobile';
            $value = $mobile;
        } elseif (!empty($nickname)) {
            $filed = 'nickname';
            $value = $nickname;
        } elseif (!empty($email)) {
            $filed = 'email';
            $value = $email;
        } elseif (!empty($birthday)) {
            $filed = 'birthday';
            $value = $birthday;
        } else {
            $filed = '';
            $value = '';
        }
        $this->accountService->personalEdit($filed, $value, $accountId);
        return Base::sucJson('成功');
    }


    /**
     * 【菜单管理】左侧菜单
     * @return mixed
     */
    public function getMenuNav($request)
    {
        $opId = $request['accountId'];
        $res  = $this->menuService->getMenuNav($opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 【菜单管理】列表
     * @param $request
     * @return mixed
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
     * @param $request
     * @return mixed
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
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array');
        $this->menuService->menuAdd($info, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】编辑
     * @param $request
     * @return mixed
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
        $info['permissionIds'] = Base::getValue($request, 'roles', '', 'array');
        $this->menuService->menuEdit($info, $id, $opId);
        return Base::sucJson('成功');
    }

    /**
     * 【菜单管理】 删除
     * @param $request
     * @return mixed
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
     * @param $request
     * @return mixed
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
     * @param $request
     * @return mixed
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
     * @param $request
     * @return mixed
     */
    public function menuEditIsHideChildren($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->menuService->menuEditIsHideChildren($id, $opId);
        return Base::sucJson('成功');
    }


    /**
     * 【账号管理】列表
     * @param $request
     * @return mixed
     */
    protected function accountList($request)
    {
        $opId  = $request['accountId'];
        $limit = Base::getValue($request, 'pageSize', '', 'integer');
        $limit = $limit ?? 10;
        $res   = $this->accountService->accountList($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     *【账号管理】添加
     * @param $request
     * @return mixed
     */
    protected function accountAdd($request)
    {
        $opId = $request['accountId'];

        $request->validate([
            'username' => 'required|max:30',
            'mobile'   => 'required|mobile',
            'roles'    => 'required|array',
            'email'    => 'nullable|email',
            'password' => 'nullable|min:8'
        ]);

        $info['nickname'] = $request->input('username');
        $info['email']    = $request->input('email', $request->input('mobile') . '@163.com');
        $info['mobile']   = $request->input('mobile');
        $info['password'] = $request->input('password');
        $info['roles']    = $request->input('roles');

        $userId = $this->accountService->accountAdd($info, $opId);

        return Base::sucJson('账号添加成功', ['id' => $userId]);
    }

    /**
     *【账号管理】编辑
     * @param $request
     * @return mixed
     */
    protected function accountEdit($request)
    {
        $opId = $request['accountId'];
        $request->validate([
            'id'       => 'required|integer',
            'username' => 'required|max:50',
            'email'    => 'required|email',
            'mobile'   => 'required|regex:/^1[3-9]\d{9}$/',
            'roles'    => 'required|array'
        ]);
        $id               = $request->input('id');
        $info['nickname'] = $request->input('username');
        $info['email']    = $request->input('email');
        $info['mobile']   = $request->input('mobile');
        $info['roles']    = $request->input('roles');

        $this->accountService->accountEdit($info, $id, $opId);

        return Base::sucJson('账号编辑成功');
    }

    /**
     *【账号管理】状态开关
     * @param $request
     * @return mixed
     */
    protected function accountEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->editStatus($id, $opId);
        return Base::sucJson('状态更新成功');
    }

    /**
     * 【账号管理】删除
     * @param $request
     * @return mixed
     */
    protected function accountDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->accountDel($id, $opId);
        return Base::sucJson('删除成功');
    }

    /**
     * 【账号管理】重置密码
     * @param $request
     * @return mixed
     */
    protected function reInitPassword($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        $this->accountService->reInitPassword($id);
        return Base::sucJson('密码重置成功');
    }


    /**
     * 【角色管理】列表
     * @param $request
     * @return mixed
     */
    protected function roleList($request)
    {
        $limit = 99;
        $opId  = $request['accountId'];
        $res   = $this->roleService->index($limit, $opId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 【角色管理】添加
     * @param $request
     * @return mixed
     */
    protected function roleAdd($request)
    {
        $opId = $request['accountId'];
        $vid  = Base::getValue($request, 'vid', '', 'required|letter|max:50');
        $name = Base::getValue($request, 'name', '', 'required|max:50');
        $this->roleService->add($vid, $name, $opId);
        return Base::sucJson('添加成功');
    }

    /**
     * 【角色管理】编辑
     * @param $request
     * @return mixed
     */
    protected function roleEdit($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $name = Base::getValue($request, 'name', '', 'required|max:50');
        $this->roleService->edit(['name' => $name], $id, $opId);
        return Base::sucJson('编辑成功');
    }

    /**
     * 【角色管理】更改状态
     * @param $request
     * @return mixed
     */
    protected function roleEditStatus($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->roleService->editStatus($id, $opId);
        return Base::sucJson('状态更新成功');
    }

    /**
     * 【角色管理】删除
     * @param $request
     * @return mixed
     */
    protected function roleDel($request)
    {
        $opId = $request['accountId'];
        $id   = Base::getValue($request, 'id', '', 'required|integer');
        $this->roleService->del($id, $opId);
        return Base::sucJson('删除成功');
    }

    /**
     * 权限列表
     * @param $request
     * @return mixed
     */
    protected function permissionsList($request)
    {
        return PermissionsController::permissionsList($request);
    }

    protected function permissionsTree()
    {
        return PermissionsController::permissionsTree();
    }

    protected function permissionsAdd($request)
    {
        return PermissionsController::permissionsAdd($request);
    }

    protected function permissionsEdit($request)
    {
        return PermissionsController::permissionsEdit($request);
    }

    protected function permissionsEditStatus($request)
    {
        return PermissionsController::permissionsEditStatus($request);
    }

    protected function permissionsDel($request)
    {
        return PermissionsController::permissionsDel($request);
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
