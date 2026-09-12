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


    public function __construct(
        protected AccountRepository    $accountRepo,
        protected PermissionRepository $permissionRepo,
    )
    {

    }

    public function ruleList(int $limit, int $opId): array
    {
        $this->checkPermissions($opId);
        return $this->permissionRepo->getParentFormatList($limit);
    }

    public function ruleListTree(): array
    {
        return $this->permissionRepo->getParentFormatTree();
    }

    public function ruleAdd(array $info, int $opId): int
    {
        $this->checkPermissions($opId);
        $pid = (int) ($info['pid'] ?? 0);
        $this->validateParentId($pid);
        $info['pid'] = $pid;
        $one = $this->permissionRepo->getInfoByVidAndPid($info['vid'], $info['pid']);
        if ($one) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return $this->permissionRepo->add($info);
    }

    public function ruleEdit(array $info, int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        if (empty($this->permissionRepo->getInfo($id))) {
            throw new CommonException('信息不存在');
        }
        $pid = (int) ($info['pid'] ?? 0);
        $this->validateParentId($pid, $id);
        $info['pid'] = $pid;
        $one = $this->permissionRepo->getInfoByVidAndPid($info['vid'], $info['pid']);
        if (!empty($one) && $one['id'] != $id) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return $this->permissionRepo->edit($info, $id);
    }

    public function ruleEditStatus(int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        $one = $this->permissionRepo->getInfo($id);
        if (empty($one)) {
            throw new CommonException('信息不存在');
        }
        $status = empty($one['status']) ? 1 : 0;
        $this->permissionRepo->edit(['status' => $status], $id);
        $this->permissionRepo->editByPid(['status' => $status], $id);
        return true;
    }


    public function ruleDel(int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        if (empty($this->permissionRepo->getInfo($id))) {
            throw new CommonException('信息不存在');
        }
        return $this->permissionRepo->del($id);
    }


    public function handleGetPermissionByAccountId(int $accountId): array
    {
        $all    = $this->permissionRepo->getAllPermissionsByAccountId($accountId);   # 所有
        $parent = $this->permissionRepo->getParentPermissionsByAccountId($accountId);# 顶级

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
            $permission[$k]['actionEntitySet'] = $this->permissionRepo->getTree($all, $v['id'], 1);
            $permission[$k]['actionList']      = null;
            $permission[$k]['actions']         = $this->permissionRepo->getTree($all, $v['id']);
            $permission[$k]['dataAccess']      = null;
            $permission[$k]['permissionId']    = $v['vid'];
            $permission[$k]['title']           = $v['title'];
        }
        return ['permissions' => $permission];
    }

    private function checkPermissions(int $accountId): void
    {
        if (!$this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }

    /**
     * 校验权限父级，防止不存在的父级和循环层级。
     */
    private function validateParentId(int $parentId, int $permissionId = 0): void
    {
        if ($parentId <= 0) {
            return;
        }
        if (empty($this->permissionRepo->getInfo($parentId))) {
            throw new CommonException('父级权限不存在');
        }
        if ($permissionId > 0 && ($parentId === $permissionId || $this->isDescendant($parentId, $permissionId))) {
            throw new CommonException('父级权限不能设置为当前权限或其子权限');
        }
    }

    /**
     * 检查待设置的父级权限是否位于当前权限的子树中。
     */
    private function isDescendant(int $parentId, int $permissionId): bool
    {
        $visited = [];
        $current = $parentId;
        while ($current > 0 && !in_array($current, $visited, true)) {
            if ($current === $permissionId) {
                return true;
            }
            $visited[] = $current;
            $parent = $this->permissionRepo->getInfo($current);
            $current = (int) ($parent['pid'] ?? 0);
        }
        return false;
    }


}
