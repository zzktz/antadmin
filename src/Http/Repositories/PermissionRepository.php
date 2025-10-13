<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Permission as Model;
use Antmin\Models\RolePermission;
use Antmin\Http\Resources\PermissionResource;
use Exception;

class PermissionRepository extends Model
{


    /**
     * 根据用户ID 获取 【全部权限】
     * @param int $accountId
     * @return array
     */
    public static function getAllPermissionsByAccountId(int $accountId): array
    {
        $allPermissionsIds = self::getAllPermissionsIdsByAccountId($accountId);
        $allPermissionsArr = Model::whereIn('id', $allPermissionsIds)->get()->toArray();
        return $allPermissionsArr ?? [];
    }

    /**
     * 根据用户ID 获取 【全部权限Ids】
     * @param int $accountId
     * @return array
     */
    public static function getAllPermissionsIdsByAccountId(int $accountId): array
    {
        if (AccountRepository::isSuperAdmin($accountId)) {
            $allPermissionsIds = self::getAllPermissionsIds();
        } else {
            $roleIds           = RoleRepository::getRolesIdsByAccountId($accountId);
            $allPermissionsIds = self::getAllPermissionsIdsByRoleIds($roleIds);
        }
        return $allPermissionsIds ?? [];
    }

    /**
     * 根据用户ID 获取 【父级权限】
     * @param int $accountId
     * @return array
     */
    public static function getParentPermissionsByAccountId(int $accountId): array
    {
        $permissionIds     = self::getParentPermissionsIdsByAccountId($accountId);
        $parentPermissions = Model::whereIn('id', $permissionIds)->get()->toArray();
        return $parentPermissions ?? [];
    }

    /**
     * 根据用户ID 获取 【父级权限IDS】
     * @param int $accountId
     * @return array
     */
    public static function getParentPermissionsIdsByAccountId(int $accountId): array
    {
        if (AccountRepository::isSuperAdmin($accountId)) {
            $parentPermissionsIds = self::getParentPermissionsIds();
        } else {
            $roleIds              = RoleRepository::getRolesIdsByAccountId($accountId);
            $parentPermissionsIds = self::getParentPermissionsByRoleIds($roleIds);
        }
        return $parentPermissionsIds ?? [];
    }

    /**
     * 根据角色 获取【全部权限IDS】
     * @param array $roleIds
     * @return array
     */
    public static function getAllPermissionsIdsByRoleIds(array $roleIds): array
    {
        $supperRoleId = RoleRepository::getSupperRoleId();
        if (in_array($supperRoleId, $roleIds)) {
            $allPermission_ids = self::getAllPermissionsIds();
        } else {
            $allPermission_ids = RolePermission::whereIn('role_id', $roleIds)->pluck('permission_id')->toArray();
        }
        return $allPermission_ids;
    }

    /**
     * 根据角色 获取【父级权限】
     * @param array $roleIds
     * @return array
     */
    public static function getParentPermissionsByRoleIds(array $roleIds): array
    {
        $parentPermissionIds = self::getParentPermissionsIdsByRoleIds($roleIds);
        $data                = Model::whereIn('id', $parentPermissionIds)->get()->toArray();
        return $data ?? [];
    }

    /**
     * 根据角色 获取【父级权限IDS】
     * @param array $roleIds
     * @return array
     */
    public static function getParentPermissionsIdsByRoleIds(array $roleIds): array
    {
        $supperRoleId = RoleRepository::getSupperRoleId();
        if (in_array($supperRoleId, $roleIds)) {
            $parentPermissionIds = self::getParentPermissionsIds();
        } else {
            $parentPermissionIds = RolePermission::whereIn('role_id', $roleIds)->pluck('permission_id')->toArray();
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
    public static function getTree(array $allPermission, int $id, int $isShowCheck = 0): ?array
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

    public static function getParentFormatToAccountList(int $limit): array
    {
        $datas = self::getParentList($limit);
        return PermissionResource::listToAccountList($datas);
    }

    public static function getParentFormatToRoleList(int $limit): array
    {
        $search['status'] = 1;
        $datas            = self::getParentList($limit, $search);
        return PermissionResource::listToAccountList($datas);
    }

    public static function getParentFormatList(int $limit): array
    {
        $datas = self::getParentList($limit);
        return PermissionResource::listToArray($datas);
    }

    public static function getParentFormatTree(int $limit = 99): array
    {
        $datas = self::getParentList($limit);
        return PermissionResource::allToArray($datas);
    }

    public static function getParentList(int $limit, array $serach = []): array
    {
        $query = Model::query();
        if (isset($serach['status']) && !empty($serach['status'])) {
            $query->where('status', $serach['status']);
        }
        $query->where('pid', 0);
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    public static function getChildList(int $id): array
    {
        return Model::where('pid', $id)->get()->toArray();
    }

    public static function getInfoByVidAndPid(string $vid, int $pid): array
    {
        $one = Model::where('vid', $vid)->where('pid', $pid)->get()->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public static function add(array $info): int
    {
        try {
            $one = Model::where('vid', $info['vid'])->where('pid', $info['pid'])->get()->first();
            if (empty($one)) {
                $one = Model::create($info);
                if ($info['pid'] == 0) {
                    self::autoAddChild($one['id']);
                }
            }
            return $one['id'];
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function edit(array $info, int $id): bool
    {
        try {
            return Model::where('id', $id)->update($info);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function del(int $id): bool
    {
        Model::where('id', $id)->delete();
        Model::where('pid', $id)->delete();
        return true;
    }

    public static function getInfo(int $id): array
    {
        $one = Model::where('id', $id)->get()->first();
        return !empty($one) ? $one->toArray() : [];
    }

    private static function getAllPermissionsIds(): array
    {
        return Model::all()->pluck('id')->toArray();
    }

    private static function getParentPermissionsIds(): array
    {
        return Model::where('pid', 0)->pluck('id')->toArray();
    }

    private static function autoAddChild($id)
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
            Model::create($add);
        }
    }

}
