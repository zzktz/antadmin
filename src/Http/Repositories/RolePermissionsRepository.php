<?php
/**
 * 账号
 */

namespace Antmin\Http\Repositories;

use Antmin\Exceptions\CommonException;
use Antmin\Models\RolePermission as Model;
use Exception;

class RolePermissionsRepository extends Model
{
    /**
     * 获取角色权限
     * @param array $rolesIds
     * @return array
     */
    public static function getPermissionsIdsByRoleIds(array $rolesIds): array
    {
        $data = Model::whereIn('role_id', $rolesIds)->pluck('permission_id')->toArray();
        $res  = array_unique($data);
        return !empty($res) ? $res : [];
    }

    public static function add(int $roleId, int $permissionId): int
    {
        try {
            $info['role_id']       = $roleId;
            $info['permission_id'] = $permissionId;
            $one                   = Model::where('role_id', $roleId)->where('permission_id', $permissionId)->get()->first();
            if (empty($one)) {
                $one = Model::create($info);
            }
            return $one['id'];
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function deleteByRoleId(int $roleId): bool
    {
        return Model::where('role_id', $roleId)->delete();
    }

}
