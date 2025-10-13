<?php

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Models\SystemSetDetail as Model;
use Exception;

class SystemSetDetailRepository extends Model
{


    public static function getList(int $limit, array $search): array
    {
        $query = Model::query();
        $query->where('id', '>', 0);
        if (!empty($search)) {
            if (isset($search['id']) && $search['id']) {
                $query->where('id', $search['id']);
            }
            if (isset($search['is_show']) && $search['is_show']) {
                $query->where('is_show', $search['is_show']);
            }
            if (isset($search['set_id']) && $search['set_id']) {
                $query->where('set_id', $search['set_id']);
            }
            if (isset($search['listorder']) && $search['listorder']) {
                $query->orderBy('listorder', $search['listorder']);
            }
            if (isset($search['title']) && $search['title']) {
                $query->where('title', 'like', '%' . $search['title'] . '%');
            }
        }
        $query->orderBy('listorder');
        $query->orderBy('id', 'desc');
        return Base::listFormat($limit, $query);
    }

    public static function add(array $info): int
    {
        try {
            return Model::create($info)->id;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function edit(array $info, int $id): bool
    {
        try {
            return Model::where('id', $id)->update($info);
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function del(int $id): bool
    {
        return Model::where('id', $id)->delete();
    }

    public static function getInfo(int $id): array
    {
        $one = Model::where('id', $id)->first();
        return !empty($one) ? $one->toArray() : [];
    }

    /**
     * 获得值
     * @param string $flag
     * @return string
     */
    public static function getValueByFlag(string $flag)
    {
        $one = Model::where('flag', $flag)->first();
        return !empty($one) ? $one['value'] : '';
    }

}
