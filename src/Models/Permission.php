<?php

namespace Antmin\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Antmin\Models\RolePermission;

class Permission extends Model
{

    protected $table = 'system_permission';
    protected $guarded = [];


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            RolePermission::where('permission_id', $model->id)->delete();
        });
    }
    
    protected function serializeDate(DateTimeInterface $date):string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
