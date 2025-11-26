<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $connection = 'log';
    protected $table = 'app_request_log';
    protected $guarded = [];


}
