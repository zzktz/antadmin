<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Role as Model;
use Antmin\Models\AccountRole;
use Antmin\Http\Resources\RoleResource;
use Exception;

class RoleRepository extends Model
{

    # 定义超级管理员角色ID
    protected static $supperRoleId = 1;


    public static function getFormatList(int $limit): array
    {
        $datas = self::getList($limit);
        return RoleResource::formatToList($datas);
    }

    public static function getFormatAccountList(int $limit): array
    {
        $datas = self::getList($limit);
        return RoleResource::formatToAccountList($datas);
    }

    public static function getList($limit): array
    {
        $query = Model::query();
        $query->orderBy('id');
        return Base::listFormat($limit, $query);
    }

    public static function add(string $vid, string $name, string $status): int
    {
        try {
            $info['vid']    = $vid;
            $info['name']   = $name;
            $info['status'] = $status;
            $one            = Model::where('name', $name)->get()->first();
            if (!$one) {
                $one = Model::create($info);
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
        return Model::where('id', $id)->delete();
    }

    public static function getRolesByAccountId(int $accountId, array $column): array
    {
        $roldIds = self::getRolesIdsByAccountId($accountId);
        return Model::whereIn('id', $roldIds)->get($column)->toArray();
    }

    /**
     * 一个账号的所有角色
     * @param int $accountId
     * @return array
     */
    public static function getRolesIdsByAccountId(int $accountId): array
    {
        if (AccountRepository::isSuperAdmin($accountId)) {
            $roleId  = self::getSupperRoleId();
            $roleIds = [$roleId];
        } else {
            $data    = AccountRole::where('account_id', $accountId)->pluck('role_id')->toArray();
            $roleIds = $data ?? [];
        }
        return array_unique($roleIds);
    }

    public static function getInfoByName(string $name): array
    {
        $one = Model::where('name', $name)->get()->first();
        return !empty($one) ? $one->toArray() : [];
    }


    public static function getSupperRoleId(): int
    {
        return self::$supperRoleId;
    }

}
