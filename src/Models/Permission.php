<?php

namespace Antmin\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_permission';
    protected $guarded = [];


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->roles()->detach();
        });
    }
    
    protected function serializeDate(DateTimeInterface $date):string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
