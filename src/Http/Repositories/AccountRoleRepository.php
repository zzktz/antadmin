<?php


namespace Antmin\Http\Repositories;


use Antmin\Models\AccountRole;

class AccountRoleRepository
{

    public function __construct(
        protected AccountRole $accountRoleModel,
    )
    {

    }

    public function isHasAccountByRoleId(int $roleId): bool
    {
        $one = $this->accountRoleModel->where('role_id', $roleId)->first();
        return !empty($one);
    }

}
