<?php
/**
 * 权限
 */

namespace Antmin\Http\Services;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\AccountRepository;
use Antmin\Http\Repositories\PermissionRepository;

class PermissionsService
{

    public static function ruleList(int $limit, int $opId): array
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        return PermissionRepository::getParentFormatList($limit);
    }

    public static function ruleListTree(): array
    {
        return PermissionRepository::getParentFormatTree(99);
    }

    public static function ruleAdd(array $info, int $opId): int
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = PermissionRepository::getInfoByVidAndPid($info['vid'], $info['pid']);
        if ($one) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return PermissionRepository::add($info);
    }

    public static function ruleEdit(array $info, int $id, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        if (empty(PermissionRepository::getInfo($id))) {
            throw new CommonException('信息不存在');
        }
        $one = PermissionRepository::getInfoByVidAndPid($info['vid'], $info['pid']);
        if (!empty($one) && $one['id'] != $id) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return PermissionRepository::edit($info, $id);
    }

    public static function ruleEditStatus(int $id, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        $one = PermissionRepository::getInfo($id);
        if (empty($one)) {
            throw new CommonException('信息不存在');
        }
        $status = empty($one['status']) ? 1 : 0;
        PermissionRepository::where('id', $id)->update(['status' => $status]);
        PermissionRepository::where('pid', $id)->update(['status' => $status]);
        return true;
    }


    public static function ruleDel(int $id, int $opId): bool
    {
        if (!AccountRepository::isSuperAdmin($opId)) {
            throw new CommonException('非超级管理员无权操作');
        }
        return PermissionRepository::del($id);
    }


    public static function handleGetPermissionByAccountId(int $accountId): array
    {
        $all    = PermissionRepository::getAllPermissionsByAccountId($accountId);   # 所有
        $parent = PermissionRepository::getParentPermissionsByAccountId($accountId);# 顶级

        $permission[0]['id']              = null;
        $permission[0]['action']          = null;
        $permission[0]['actionEntitySet'] = null;
        $permission[0]['actionList']      = null;
        $permission[0]['actions']         = null;
        $permission[0]['dataAccess']      = null;
        $permission[0]['permissionId']    = null;
        $permission[0]['title']           = null;

        if (empty($parent)) {
            return ['permissions' => $permission];
        }
        foreach ($parent as $k => $v) {
            $permission[$k]['id']              = $v['id'];
            $permission[$k]['action']          = $v['vid'];
            $permission[$k]['actionEntitySet'] = PermissionRepository::getTree($all, $v['id'], 1);
            $permission[$k]['actionList']      = null;
            $permission[$k]['actions']         = PermissionRepository::getTree($all, $v['id']);
            $permission[$k]['dataAccess']      = null;
            $permission[$k]['permissionId']    = $v['vid'];
            $permission[$k]['title']           = $v['title'];
        }
        return ['permissions' => $permission];
    }


}
