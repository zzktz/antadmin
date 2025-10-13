<?php

namespace Antmin\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Safe extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_safe_report';
    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date):string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
