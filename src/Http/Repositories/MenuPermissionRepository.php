<?php
/**
 * 菜单绑定权限
 */

namespace Antmin\Http\Repositories;

use Antmin\Models\MenuPermission;

class MenuPermissionRepository
{

    public function __construct(
        protected MenuPermission $menuPermissionModel,
    )
    {

    }


    public function getMenuIdsByPermissionIds(array $permissionIds): array
    {
        $defIds = [1, 2, 10, 16];
        if (empty($permissionIds)) {
            return $defIds;
        }
        $res = $this->menuPermissionModel->whereIn('permission_id', $permissionIds)
            ->pluck('menu_id')
            ->toArray();
        if (empty($res)) {
            return $defIds;
        }
        return array_merge($res, $defIds);
    }

}
