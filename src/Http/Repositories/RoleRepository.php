<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Models\Role;
use Antmin\Models\AccountRole;
use Antmin\Http\Resources\RoleResource;


class RoleRepository
{


    public function __construct(
        protected Role                 $roleModel,
        protected AccountRole          $accountRoleModel,
        protected PermissionRepository $permissionRepo,
    )
    {

    }


    public function getFormatList(int $limit): array
    {
        $datas = $this->getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }
        $data = $datas['data'];
        foreach ($data as $k => $v) {
            $permissions = $this->permissionRepo->getAllPermissionsIdsByRoleIds([$v['id']]);

            $rest[$k]                 = $v;
            $rest[$k]['permissions']  = $permissions;
            $rest[$k]['isShowDelete'] = $v['id'] == 1 ? 0 : 1;
        }
        $temp['current']  = $datas['pageNo'];
        $temp['pageSize'] = $datas['pageSize'];
        $temp['total']    = $datas['totalCount'];

        $res['pagination'] = $temp;
        $res['data']       = $rest ?? [];
        return $res;
    }

    public function getFormatAccountList(int $limit): array
    {
        $datas = $this->getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }

        foreach ($datas['data'] as $k => $v) {
            $permissions = $this->permissionRepo->getAllPermissionsIdsByRoleIds([$v['id']]);

            $rest[$k]['id']           = $v['id'];
            $rest[$k]['name']         = $v['vid'];
            $rest[$k]['title']        = $v['name'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['permissionId'] = $permissions;
        }
        $temp['current']  = $datas['pageNo'];
        $temp['pageSize'] = $datas['pageSize'];
        $temp['total']    = $datas['totalCount'];

        $res['pagination'] = $temp;
        $res['data']       = $rest ?? [];
        return $res;
    }

    public function getList($limit): array
    {
        $query = $this->roleModel->query();
        $query->orderBy('id');
        return Base::listFormat($limit, $query);
    }

    public function add(array $info): int
    {
        $in['vid']  = $info['vid'];
        $in['name'] = $info['name'];
        return $this->roleModel->create($in)->id;
    }

    public function edit(array $info, int $id): bool
    {
        $one = $this->roleModel->find($id);
        return $one->update($info);
    }

    public function del(int $id): bool
    {
        $one = $this->roleModel->find($id);
        return $one->delete();
    }

    public function getInfo(int $id): array
    {
        $one = $this->roleModel->find($id);
        return empty($one) ? [] : $one->toArray();
    }


    /**
     * 一个账号的所有角色 信息
     * @param int $accountId
     * @param array $column
     * @return array
     */
    public function getRolesByAccountId(int $accountId, array $column): array
    {
        return $this->roleModel->getRolesByAccountId($accountId, $column);
    }

    /**
     * 一个账号的所有角色
     * @param int $accountId
     * @return array
     */
    public function getRolesIdsByAccountId(int $accountId): array
    {
        return $this->roleModel->getRolesIdsByAccountId($accountId);
    }

    public function getInfoByName(string $name): array
    {
        $one = $this->roleModel->where('name', $name)->first();
        return !empty($one) ? $one->toArray() : [];
    }


    public function getInfoByVid(string $vid): array
    {
        $one = $this->roleModel->where('vid', $vid)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    public function getSupperRoleId(): int
    {
        return $this->roleModel->getSupperRoleId();
    }

}
