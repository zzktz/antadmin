<?php

namespace Antmin\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRole extends Model
{
    protected $connection = 'admin';
	protected $table = 'system_account_role';
	protected $guarded = [];
	
	public function role(){
		return $this->belongsTo(Role::class,'role_id','id')->select(['id','name','vid']); //第1个参数自身关键字   第2个参数是关联表中关键字
	}
	
}
