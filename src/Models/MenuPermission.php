<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_menu_permission';
    protected $guarded = [];


}
