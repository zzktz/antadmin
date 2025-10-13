<?php

namespace Antmin\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'admin';
	protected $table = 'system_message';
	protected $guarded = ['id'];
	
	protected function serializeDate(DateTimeInterface $date):string
	{
		return $date->format('Y-m-d H:i:s');
	}
}
