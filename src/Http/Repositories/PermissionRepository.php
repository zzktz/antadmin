<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Models\Permission;
use Antmin\Models\Role;
use Antmin\Models\RolePermission;
use Antmin\Http\Resources\PermissionResource;

class PermissionRepository
{

    public function __construct(
        protected Permission     $permissionModel,
        protected RolePermission $rolePermissionModel,
        protected Role           $roleModel,
    )
    {

    }

    /**
     * 根据用户ID 获取 【全部权限】
     * @param int $accountId
     * @return array
     */
    public function getAllPermissionsByAccountId(int $accountId): array
    {
        $allPermissionsIds = $this->getAllPermissionsIdsByAccountId($accountId);
        $allPermissionsArr = $this->permissionModel->whereIn('id', $allPermissionsIds)->get()->toArray();
        return $allPermissionsArr ?? [];
    }

    /**
     * 根据用户ID 获取 【全部权限Ids】
     * @param int $accountId
     * @return array
     */
    public function getAllPermissionsIdsByAccountId(int $accountId): array
    {
        if ($accountId == 1) {
            $allPermissionsIds = $this->getAllPermissionsIds();
        } else {
            $roleIds           = $this->roleModel->getRolesIdsByAccountId($accountId);
            $allPermissionsIds = $this->getAllPermissionsIdsByRoleIds($roleIds);
        }
        return $allPermissionsIds ?? [];
    }

    /**
     * 根据用户ID 获取 【父级权限】
     * @param int $accountId
     * @return array
     */
    public function getParentPermissionsByAccountId(int $accountId): array
    {
        $permissionIds     = $this->getParentPermissionsIdsByAccountId($accountId);
        $parentPermissions = $this->permissionModel->whereIn('id', $permissionIds)->get()->toArray();
        return $parentPermissions ?? [];
    }

    /**
     * 根据用户ID 获取 【父级权限IDS】
     * @param int $accountId
     * @return array
     */
    public function getParentPermissionsIdsByAccountId(int $accountId): array
    {
        if ($accountId == 1) {
            $parentPermissionsIds = $this->getParentPermissionsIds();
        } else {
            $roleIds              = $this->roleModel->getRolesIdsByAccountId($accountId);
            $parentPermissionsIds = $this->getParentPermissionsByRoleIds($roleIds);
        }
        return $parentPermissionsIds ?? [];
    }

    /**
     * 根据角色 获取【全部权限IDS】
     * @param array $roleIds
     * @return array
     */
    public function getAllPermissionsIdsByRoleIds(array $roleIds): array
    {
        $supperRoleId = $this->roleModel->getSupperRoleId();
        if (in_array($supperRoleId, $roleIds)) {
            $allPermission_ids = self::getAllPermissionsIds();
        } else {
            $allPermission_ids = $this->rolePermissionModel->whereIn('role_id', $roleIds)
                ->pluck('permission_id')
                ->toArray();
        }
        return $allPermission_ids;
    }

    /**
     * 根据角色 获取【父级权限】
     * @param array $roleIds
     * @return array
     */
    public function getParentPermissionsByRoleIds(array $roleIds): array
    {
        $parentPermissionIds = $this->getParentPermissionsIdsByRoleIds($roleIds);

        $data = $this->permissionModel->whereIn('id', $parentPermissionIds)
            ->get()
            ->toArray();
        return $data ?? [];
    }

    /**
     * 根据角色 获取【父级权限IDS】
     * @param array $roleIds
     * @return array
     */
    public function getParentPermissionsIdsByRoleIds(array $roleIds): array
    {
        $supperRoleId = $this->roleModel->getSupperRoleId();
        if (in_array($supperRoleId, $roleIds)) {
            $parentPermissionIds = $this->getParentPermissionsIds();
        } else {
            $parentPermissionIds = $this->permissionModel->whereIn('role_id', $roleIds)
                ->pluck('permission_id')
                ->toArray();
        }
        return $parentPermissionIds;
    }


    /**
     * 格式树
     * @param array $allPermission
     * @param int $id
     * @param int $isShowCheck
     * @return array|null 注意返回格式 空的时候返回 null
     */
    public function getTree(array $allPermission, int $id, int $isShowCheck = 0): ?array
    {
        foreach ($allPermission as $k => $v) {
            if ($v['pid'] == $id) {
                $temp[$k]['action'] = $v['vid'];
                $temp[$k]['title']  = $v['title'];
                if ($isShowCheck == 1) {
                    $temp[$k]['defaultCheck'] = false;
                }
            }
        }
        return empty($temp) ? null : array_values($temp);
    }

    public function getParentFormatToAccountList(int $limit): array
    {
        $datas = $this->getParentList($limit);
        return PermissionResource::listToAccountList($datas);
    }

    public function getParentFormatToRoleList(int $limit): array
    {
        $search['status'] = 1;
        $datas            = $this->getParentList($limit, $search);
        return PermissionResource::listToAccountList($datas);
    }

    public function getParentFormatList(int $limit): array
    {
        $datas = $this->getParentList($limit);
        return PermissionResource::listToArray($datas);
    }

    public function getParentFormatTree(int $limit = 99): array
    {
        $datas = $this->getParentList($limit);
        return PermissionResource::allToArray($datas);
    }

    public function getParentList(int $limit, array $serach = []): array
    {
        $query = $this->permissionModel->query();
        if (!empty($serach['status'])) {
            $query->where('status', $serach['status']);
        }
        $query->where('pid', 0);
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    public function getChildList(int $id): array
    {
        return $this->permissionModel->where('pid', $id)->get()->toArray();
    }

    public function getInfoByVidAndPid(string $vid, int $pid): array
    {
        $one = $this->permissionModel->where('vid', $vid)
            ->where('pid', $pid)
            ->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public function add(array $info): int
    {

        $one = $this->permissionModel->where('vid', $info['vid'])
            ->where('pid', $info['pid'])
            ->first();
        if (empty($one)) {
            $one = $this->permissionModel->create($info);
            if ($info['pid'] == 0) {
                $this->autoAddChild($one['id']);
            }
        }
        return $one->id;

    }

    public function edit(array $info, int $id): bool
    {
        return $this->permissionModel->where('id', $id)->update($info);
    }

    public function del(int $id): bool
    {
        $this->permissionModel->where('id', $id)->delete();
        $this->permissionModel->where('pid', $id)->delete();
        return true;
    }

    public function getInfo(int $id): array
    {
        $one = $this->permissionModel->where('id', $id)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    private function getAllPermissionsIds(): array
    {
        return $this->permissionModel->all()->pluck('id')->toArray();
    }

    private function getParentPermissionsIds(): array
    {
        return $this->permissionModel->where('pid', 0)->pluck('id')->toArray();
    }

    private function autoAddChild(int $id): void
    {
        for ($x = 0; $x <= 3; $x++) {
            if ($x == 0) {
                $vid   = 'view';
                $title = '查看';
            } elseif ($x == 1) {
                $vid   = 'add';
                $title = '添加';
            } elseif ($x == 2) {
                $vid   = 'update';
                $title = '更新';
            } elseif ($x == 3) {
                $vid   = 'delete';
                $title = '删除';
            }
            $add['vid']         = $vid ?? '';
            $add['action_rule'] = $vid ?? '';
            $add['title']       = $title ?? '';
            $add['pid']         = $id;
            $this->permissionModel->create($add);
        }
    }

}
