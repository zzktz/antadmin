<?php

namespace Antmin\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class SystemSetDetail extends Model
{

    protected $table = 'system_set_detail';
    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date):string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function item()
    {
        return $this->belongsTo(SystemSetItem::class, 'item_type', 'type')->select(['id', 'title', 'type']);
    }
}
