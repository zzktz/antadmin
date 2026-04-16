<?php

namespace Antmin\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'system_role_permission';

    protected $guarded = ['id']; //不可以注入的数据字段

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }


}
