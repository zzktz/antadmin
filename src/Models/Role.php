<?php

namespace Antmin\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table   = 'system_role';
    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }


    /**
     * 一个账号的所有角色 信息
     * @param int $accountId
     * @param array $column
     * @return array
     */
    public function getRolesByAccountId(int $accountId, array $column): array
    {
        $roldIds = $this->getRolesIdsByAccountId($accountId);
        return self::whereIn('id', $roldIds)->get($column)->toArray();
    }

    /**
     * 一个账号的所有角色
     * @param int $accountId
     * @return array
     */
    public function getRolesIdsByAccountId(int $accountId): array
    {
        if ($accountId == 1) {
            $roleIds = [1];
        } else {
            $accountRoleModel = new AccountRole();

            $data    = $accountRoleModel->where('account_id', $accountId)
                ->pluck('role_id')
                ->toArray();
            $roleIds = $data ?? [];
        }
        return array_unique($roleIds);
    }


    public function getSupperRoleId(): int
    {
        return 1;
    }

}
