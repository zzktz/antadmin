<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $table = 'app_request_log';
    protected $guarded = [];
    protected $casts = [
        'header' => 'array',
        'params' => 'array',
    ];

    public function getConnectionName()
    {
        return config('antmin.database.log_connection') ?: config('database.default');
    }


}
