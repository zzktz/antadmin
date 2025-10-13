<?php
/**
 * 角色
 */

namespace Antmin\Http\Services;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\AccountRoleRepository;
use Antmin\Http\Repositories\PermissionRepository;
use Antmin\Http\Repositories\RolePermissionsRepository;

class RoleService
{


    public static function roleList(int $limit, int $opId): array
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $res['roles'] = RoleRepository::getFormatList($limit);
        $res['rules'] = PermissionRepository::getParentFormatToRoleList(99);
        return $res;
    }

    public static function roleAdd(string $vid, string $name, string $status, int $opId): int
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $info = RoleRepository::getInfoByName($name);
        if ($info) {
            throw new CommonException('角色名已存在');
        }
        return RoleRepository::add($vid, $name, $status);
    }


    public static function edit(int $roleId, string $name, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        if ($roleId == RoleRepository::getSupperRoleId()) {
            throw new CommonException('超级管理员角色不可编辑');
        }
        $info = RoleRepository::getInfoByName($name);
        if ($info && $roleId != $info['id']) {
            throw new CommonException('角色名已存在');
        }
        if (!empty($name)) {
            $up['name'] = $name;
            RoleRepository::edit($up, $roleId);
        }
        return true;
    }

    public static function roleDel(int $roleId, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        if ($roleId == RoleRepository::getSupperRoleId()) {
            throw new CommonException('超级管理员角色不可删除');
        }
        $isRes = AccountRoleRepository::isInfoByRoleId($roleId);
        if ($isRes) {
            throw new CommonException('该角色存在账号中，请先处理');
        }
        # 删除角色关联的权限
        RolePermissionsRepository::deleteByRoleId($roleId);
        # 删除角色
        return RoleRepository::del($roleId);
    }

    public static function roleEditStatus(int $roleId, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $info   = RoleRepository::find($roleId);
        $status = empty($info['status']) ? 1 : 0;
        return RoleRepository::where('id', $roleId)->update(['status' => $status]);
    }


    public static function handleDelRolePermissions(int $roleId)
    {
        return RolePermissionsRepository::deleteByRoleId($roleId);
    }


    public static function handleAddRolePermissions(array $rules, int $roleId)
    {
        if (empty($rules)) {
            return false;
        }
        foreach ($rules as $permissionId) {
            $one = PermissionRepository::find($permissionId);
            $pid = !empty($one['pid']) ? $one['pid'] : 0;
            $two = RolePermissionsRepository::where('role_id', $pid)->where('permission_id', $permissionId)->get()->first();
            if (empty($two) && $pid > 0) {
                RolePermissionsRepository::add($roleId, $pid);
            }
            $three = RolePermissionsRepository::where('role_id', $roleId)->where('permission_id', $permissionId)->get()->first();
            if (empty($three)) {
                RolePermissionsRepository::add($roleId, $permissionId);
            }
        }
        return true;
    }


}
