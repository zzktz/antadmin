<?php
/**
 * 消息
 */

namespace Antmin\Http\Services;

use Antmin\Http\Resources\MessageResource;
use Antmin\Http\Repositories\MessageRepository;

class MessageService
{
    public static function handleNoReadListMessage(int $limit, int $accountId)
    {
        $datas = MessageRepository::getNoReadList($limit, $accountId);
        return MessageResource::listToArray($datas);
    }

}
