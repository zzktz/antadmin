<?php
/**
 *
 */

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\ItemDetail as Model;
use Exception;

class ItemDetailRepository extends Model
{
    public static function getList(int $limit, int $itemId): array
    {
        $query = Model::query();
        $query->where('item_id', $itemId);
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    /**
     * 添加
     * @param string $value
     * @param string $flag
     * @param int $itemId
     * @return int
     */
    public static function add(string $value, string $flag, int $itemId): int
    {
        try {
            $add['item_id'] = $itemId;
            $add['value']   = $value;
            $add['flag']    = $flag;
            return Model::create($add)->id;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }


    public static function getInfo(int $id): array
    {
        $one = Model::find($id);
        return $one ? $one->toArray() : [];
    }

    public static function getTreeByItemId(int $itemId): array
    {
        $query = Model::query();
        $query->where('item_id', $itemId);
        $query->where('is_open', 1);
        $query->orderBy('listorder', 'asc');
        return $query->get(['id', 'value', 'flag'])->toArray();
    }

}
