<?php

namespace Antmin\Models;

use Illuminate\Database\Eloquent\Model;

class OperateLog extends Model
{
    protected $table = 'app_operate_log';

    protected $guarded = ['id']; //不可以注入的数据字段

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id')->select(['id', 'nickname']);
    }


}
