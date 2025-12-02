<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class SystemSetItem extends Model
{
    protected $table = 'system_set_item';
    protected $guarded = []; //不可以注入的数据字段

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }


}
