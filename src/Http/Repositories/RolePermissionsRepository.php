<?php
/**
 * 账号
 */

namespace Antmin\Http\Repositories;


use Antmin\Models\RolePermission;

class RolePermissionsRepository
{

    public function __construct(
        protected RolePermission       $rolePermissionModel,
        protected PermissionRepository $permissionRepo,
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
        return DB::transaction(function () use ($roleId, $permissionId) {
            # 获取权限信息
            $permission = $this->permissionRepo->getInfo($permissionId);
            if (!$permission) {
                throw new CommonException('权限不存在');
            }

            # 如果有父级权限，确保父级权限已分配
            if (!empty($permission['pid'])) {
                $this->ensureParentPermissionExists($roleId, $permission['pid']);
            }

            # 创建或获取角色权限关联
            $rolePermission = $this->rolePermissionModel->firstOrCreate([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]);

            return $rolePermission->id;
        });
    }

    /**
     * 确保父级权限存在
     */
    private function ensureParentPermissionExists(int $roleId, int $parentPermissionId): void
    {
        $exists = $this->rolePermissionModel
            ->where('role_id', $roleId)
            ->where('permission_id', $parentPermissionId)
            ->exists();

        if (!$exists) {
            $this->rolePermissionModel->create([
                'role_id'       => $roleId,
                'permission_id' => $parentPermissionId,
            ]);
        }
    }


    /**
     * 删除1个角色下的权限
     * @param int $roleId
     * @return void
     */
    public function deleteByRoleId(int $roleId): void
    {
        $this->rolePermissionModel->where('role_id', $roleId)->delete();
    }


}
