<?php
/**
 * 菜单
 */

namespace Antmin\Http\Services;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\MenuRepository;
use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\MenuPermissionRepository;
use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\RolePermissionsRepository;

class MenuService
{

    /**
     * 左侧菜单
     * @param int $accountId
     * @return array
     */
    public static function getMenuNav(int $accountId): array
    {
        $roleIds       = RoleRepository::getRolesIdsByAccountId($accountId);
        $permissionIds = RolePermissionsRepository::getPermissionsIdsByRoleIds($roleIds);
        $menuIds       = MenuPermissionRepository::getMenuIdsByPermissionIds($permissionIds);
        $isAdmin       = AccountRepository::isSuperAdmin($accountId);
        $query         = MenuRepository::query();

        if ($isAdmin) {
            $query->where('id', '>', 0);
        } else {
            $query->whereIn('id', $menuIds);
        }
        $query->orderBy('listorder', 'asc');
        $query->orderBy('id', 'desc');
        $data = $query->get()->toArray();
        if (empty($data)) {
            return [];
        }
        foreach ($data as $k => $v) {
            $hidden                    = empty($v['is_show']);
            $res[$k]['id']             = $v['id'];
            $res[$k]['name']           = $v['page_name'];
            $res[$k]['component']      = $v['component'];
            $res[$k]['path']           = $v['route_path'];
            $res[$k]['parentId']       = $v['parent_id'];
            $res[$k]['redirect']       = $v['redirect'];
            $res[$k]['isHideChildren'] = $v['is_hide_children'];
            $res[$k]['meta']           = ['title' => $v['title'], 'icon' => $v['icon'], 'hidden' => $hidden, 'hideChildren' => (bool)$v['is_hide_children'], 'permission' => []];
        }
        return $res ?? [];
    }

    /**
     * 菜单列表
     * @param int $parentId
     * @param array $arr
     * @return array
     */
    public static function menuList(int $parentId, array $arr = []): array
    {
        $allData = MenuRepository::getAllCacheData();
        $records = collect($allData);
        # 首先获取父级记录
        $data   = $records->filter(function ($record) use ($parentId) {
            return $record['parent_id'] === $parentId;
        });
        $result = [];
        foreach ($data as $v) {
            $pid      = $v['parent_id'] ?? 0;
            $key      = $pid . '-' . $v['id'];
            $child    = self::menuList($v['id'], $v);
            $result[] = [
                'id'             => $v['id'],
                'title'          => $v['title'],
                'icon'           => $v['icon'],
                'component'      => $v['component'],
                'pageName'       => $v['page_name'],
                'routePath'      => $v['route_path'],
                'listorder'      => $v['listorder'],
                'key'            => $key,
                'value'          => $key,
                'parentId'       => $v['parent_id'],
                'parentName'     => !empty($arr) ? $arr['title'] : '顶级',
                'isChild'        => !empty($child) ? 1 : 0,
                'children'       => $child,
                'isDelete'       => $v['id'] < 20 ? 0 : 1,
                'isShow'         => $v['is_show'],
                'redirect'       => $v['redirect'],
                'roles'          => MenuPermissionRepository::where('menu_id', $v['id'])->pluck('permission_id')->toArray(),
                'isHideChildren' => $v['is_hide_children']
            ];
        }
        return collect($result)->sortBy('listorder')->values()->all();
    }


    /**
     * 菜单添加
     * @param array $info
     * @param int $accountId
     * @return int
     */
    public static function menuAdd(array $info, int $accountId): int
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $permissionIds     = $info['permissionIds'];
        $add['parent_id']  = $info['parentId'];
        $add['title']      = $info['title'];
        $add['icon']       = $info['icon'];
        $add['page_name']  = $info['pageName'];
        $add['route_path'] = $info['routePath'];
        $add['component']  = $info['component'];
        $add['redirect']   = $info['redirect'];
        $resId             = MenuRepository::add($add);
        MenuPermissionRepository::where('menu_id', $resId)->delete();
        if (!empty($permissionIds)) {
            foreach ($permissionIds as $v) {
                MenuPermissionRepository::create(['menu_id' => $resId, 'permission_id' => $v]);
            }
        }
        return $resId;
    }

    /**
     * 菜单编辑
     * @param array $info
     * @param int $id
     * @param int $accountId
     * @return bool
     */
    public static function menuEdit(array $info, int $id, int $accountId): bool
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = MenuRepository::find($id);
        if (empty($one)) {
            throw new CommonException('菜单信息不存在');
        }
        $permissionIds     = $info['permissionIds'];
        $add['parent_id']  = $info['parentId'];
        $add['title']      = $info['title'];
        $add['icon']       = $info['icon'];
        $add['page_name']  = $info['pageName'];
        $add['route_path'] = $info['routePath'];
        $add['component']  = $info['component'];
        $add['redirect']   = $info['redirect'];
        MenuPermissionRepository::where('menu_id', $id)->delete();
        if (!empty($permissionIds)) {
            foreach ($permissionIds as $v) {
                MenuPermissionRepository::create(['menu_id' => $id, 'permission_id' => $v]);
            }
        }
        return MenuRepository::edit($add, $id);
    }

    /**
     * 菜单删除
     * @param int $id
     * @param int $accountId
     * @return bool
     */
    public static function menuDel(int $id, int $accountId): bool
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $data = MenuRepository::getDataByParentId($id);
        if (!empty($data)) {
            throw new CommonException('有子级不可删除');
        }
        return MenuRepository::del($id);
    }

    /**
     * 菜单编辑排序
     * @param int $listorder
     * @param int $id
     * @param int $accountId
     * @return bool
     */
    public static function menuEditListorder(int $listorder, int $id, int $accountId): bool
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = MenuRepository::find($id);
        if (empty($one)) {
            throw new CommonException('菜单信息不存在');
        }
        $up['listorder'] = $listorder;
        return MenuRepository::edit($up, $id);
    }

    /**
     * 菜单编辑是否显示
     * @param int $id
     * @param int $accountId
     * @return bool
     */
    public static function menuEditIsShow(int $id, int $accountId): bool
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = MenuRepository::find($id);
        if (empty($one)) {
            throw new CommonException('菜单信息不存在');
        }
        $up['is_show'] = empty($one['is_show']) ? 1 : 0;
        return MenuRepository::edit($up, $id);
    }

    /**
     * 菜单编辑是否隐藏子菜单
     * @param int $id
     * @param int $accountId
     * @return bool
     */
    public static function menuEditIsHideChildren(int $id, int $accountId): bool
    {
        if (!AccountRepository::isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = MenuRepository::find($id);
        if (empty($one)) {
            throw new CommonException('菜单信息不存在');
        }
        $up['is_hide_children'] = empty($one['is_hide_children']) ? 1 : 0;
        return MenuRepository::edit($up, $id);
    }

}
