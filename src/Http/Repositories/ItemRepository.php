<?php
/**
 * 选项
 */

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\Item as Model;
use Exception;

class ItemRepository extends Model
{
    public static function getList(int $limit, array $search = []): array
    {
        $query = Model::query();
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    /**
     * 添加
     * @param array $info
     * @return int
     */
    public static function add(array $info): int
    {
        try {
            $one = Model::where('flag', $info['flag'])->first();
            if (empty($one)) {
                $add['flag']  = $info['flag'];
                $add['title'] = $info['title'];
                return Model::create($add)->id;
            }
            return 0;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

}
