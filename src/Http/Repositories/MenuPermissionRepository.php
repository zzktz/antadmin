<?php
/**
 * 菜单绑定权限
 */

namespace Antmin\Http\Repositories;

use Antmin\Models\MenuPermission as Model;

class MenuPermissionRepository extends Model
{

    public static function getMenuIdsByPermissionIds(array $permissionIds): array
    {
        $defIds = [1, 2, 10, 16];
        if (empty($permissionIds)) {
            return $defIds;
        }
        $res = Model::whereIn('permission_id', $permissionIds)->pluck('menu_id')->toArray();
        if (empty($res)) {
            return $defIds;
        }
        return array_merge($res, $defIds);
    }

}