<?php

namespace Antmin\Models;


use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $connection = 'admin';
    protected $table = 'system_item';
    protected $guarded = [];


}
