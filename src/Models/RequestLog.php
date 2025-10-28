<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $connection = 'admin';
    protected $table = 'app_request_log';
    protected $guarded = [];


}
