<?php

namespace Antmin\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_role_permission';
    protected $guarded = [];


}
