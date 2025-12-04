<?php
/**
 * 账号
 */

namespace Antmin\Http\Repositories;


use Antmin\Models\RolePermission;

class RolePermissionsRepository
{

    public function __construct(
        protected RolePermission $rolePermissionModel
    )
    {

    }

    /**
     * 获取角色权限
     * @param array $rolesIds
     * @return array
     */
    public function getPermissionsIdsByRoleIds(array $rolesIds): array
    {
        $data = $this->rolePermissionModel->whereIn('role_id', $rolesIds)
            ->pluck('permission_id')
            ->toArray();
        $res  = array_unique($data);
        return !empty($res) ? $res : [];
    }

    public function add(int $roleId, int $permissionId): int
    {
        $info['role_id']       = $roleId;
        $info['permission_id'] = $permissionId;

        $one = $this->rolePermissionModel->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();
        if (empty($one)) {
            $one = $this->rolePermissionModel->create($info);
        }
        return $one['id'];
    }

    public function deleteByRoleId(int $roleId): void
    {
        $this->rolePermissionModel->where('role_id', $roleId)->delete();
    }


}
