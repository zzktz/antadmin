<?php
/**
 * 消息
 */

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Models\Message as Model;

class MessageRepository extends Model
{
	
	public static function getNoReadList(int $limit, int $accountId):array
	{
		$query = Model::query();
		$query->where('is_read', 0);
		$query->where('account_id', $accountId);
		$query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
	}
	
	
}
