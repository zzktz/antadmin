<?php
/**
 * 系统设置仓储。
 */

namespace Antmin\Http\Repositories;

use Antmin\Common\Base;
use Antmin\Models\SystemSet as Model;

class SystemSetRepository extends Model
{
    /**
     * 获取系统设置列表。
     */
    public static function getList(array $search = [], int $limit = 10): array
    {
        $query = Model::query();
        if (isset($search['is_show'])) {
            $query->where('is_show', (int) $search['is_show']);
        }
        return Base::listFormat($limit, $query->orderBy('listorder')->orderByDesc('id'));
    }

    public static function getInfoByTitle(string $title): array
    {
        $one = Model::where('title', $title)->first();
        return $one ? $one->toArray() : [];
    }

    public static function add(array $info): int
    {
        return Model::create($info)->id;
    }

    public static function edit(array $info, int $id): bool
    {
        return (bool) Model::whereKey($id)->update($info);
    }

    public static function del(int $id): bool
    {
        return (bool) Model::whereKey($id)->delete();
    }

    public static function getInfo(int $id): array
    {
        $one = Model::find($id);
        return $one ? $one->toArray() : [];
    }
}
