<?php

namespace Antmin\Http\Repositories;


use Antmin\Models\Safe as Model;

class SafeRepository extends Model
{


    /**
     * 添加登陆记录
     * @param int $accountId
     * @param int $status 1成功 0失败
     */
    public static function addReport(int $accountId, int $status): void
    {
        Model::create(['account_id' => $accountId, 'status' => $status]);
    }


}
