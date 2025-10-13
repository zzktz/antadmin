<?php


namespace Antmin\Http\Repositories;


use Antmin\Models\AccountRole as Model;

class AccountRoleRepository extends Model
{


    public static function isInfoByRoleId(int $roleId): bool
    {
        $one = Model::where('role_id', $roleId)->first();
        return !empty($one);
    }

}