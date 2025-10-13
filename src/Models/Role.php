<?php

namespace Antmin\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_role';
    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date):string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
