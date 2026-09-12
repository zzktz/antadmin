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
use Illuminate\Support\Facades\DB;

class RoleService
{

    public function __construct(
        protected RoleRepository            $roleRepo,
        protected AccountRepository         $accountRepo,
        protected PermissionRepository      $permissionRepo,
        protected RolePermissionsRepository $rolePermissionsRepo,
        protected AccountRoleRepository     $accountRoleRepo,
    )
    {

    }

    /**
     * 列表
     * @param int $limit
     * @param int $accountId
     * @return array
     */
    public function index(int $limit, int $accountId): array
    {
        # 权限验证
        $this->checkPermissions($accountId);

        $res['roles'] = $this->roleRepo->getFormatList($limit);
        $res['rules'] = $this->permissionRepo->getParentFormatToRoleList(99);
        return $res;
    }

    /**
     * 添加
     * @param string $vid
     * @param string $name
     * @param int $accountId
     * @return int
     */
    public function add(string $vid, string $name, int $accountId): int
    {
        # 权限验证
        $this->checkPermissions($accountId);

        $one = $this->roleRepo->getInfoByVid($vid);
        if (!empty($one)) {
            throw new CommonException('角色标识已存在');
        }

        $info = $this->roleRepo->getInfoByName($name);
        if (!empty($info)) {
            throw new CommonException('角色名已存在');
        }

        $info['vid']  = $vid;
        $info['name'] = $name;
        return $this->roleRepo->add($info);
    }


    public function edit(array $info, int $id, int $accountId): bool
    {
        # 权限验证
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        if (empty($this->roleRepo->getInfo($id))) {
            throw new CommonException('角色不存在');
        }

        $name = $info['name'];
        $one  = $this->roleRepo->getInfoByName($name);
        if ($one && $id != $one['id']) {
            throw new CommonException('角色名已存在');
        }
        if (!empty($name)) {
            $up['name'] = $name;
            $this->roleRepo->edit($up, $id);
        }
        return true;
    }

    public function roleRuleEdit(array $rules, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        if (empty($this->roleRepo->getInfo($id))) {
            throw new CommonException('角色不存在');
        }
        return DB::transaction(function () use ($rules, $id) {
            $this->rolePermissionsRepo->deleteByRoleId($id);
            foreach (array_unique(array_map('intval', $rules ?: [])) as $permissionId) {
                $this->rolePermissionsRepo->add($id, $permissionId);
            }
            return true;
        });
    }


    public function del(int $id, int $accountId): bool
    {
        # 权限验证
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        if (empty($this->roleRepo->getInfo($id))) {
            throw new CommonException('角色不存在');
        }
        # 判断角色中是否有成员
        $isHas = $this->accountRoleRepo->isHasAccountByRoleId($id);
        if ($isHas) {
            throw new CommonException('该角色存在账号中，请先处理');
        }
        return DB::transaction(function () use ($id) {
            $this->rolePermissionsRepo->deleteByRoleId($id);
            return $this->roleRepo->del($id);
        });
    }

    public function editStatus(int $id, int $accountId): bool
    {
        # 权限验证
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        $info   = $this->roleRepo->getInfo($id);
        if (empty($info)) {
            throw new CommonException('角色不存在');
        }
        $status = empty($info['status']) ? 1 : 0;
        return $this->roleRepo->edit(['status' => $status], $id);
    }


    private function checkPermissions(int $accountId): void
    {
        if (!$this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }

    private function checkSupperRoleId(int $id): void
    {
        if ($id == $this->roleRepo->getSupperRoleId()) {
            throw new CommonException('超级管理员角色不可删除');
        }
    }

}
