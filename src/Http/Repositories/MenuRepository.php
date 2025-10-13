<?php
/**
 * 菜单
 */

namespace Antmin\Http\Repositories;

use Antmin\Models\Menu as Model;

class MenuRepository extends Model
{
    public static function add(array $info): int
    {
        return Model::create($info)->id;
    }

    public static function edit(array $info, int $id): bool
    {
        return Model::find($id)->update($info);
    }

    public static function del(int $id): bool
    {
        return Model::find($id)->delete();
    }

    public static function getInfo(int $id): array
    {
        $arr = Model::getAllCacheData();
        $res = collect($arr)->keyBy('id');
        return $res->all()[$id] ?? [];
    }

    public static function getDataByParentId(int $parentId): array
    {
        $allData  = Model::getAllCacheData();
        $records  = collect($allData);
        $fRecords = $records->filter(function ($record) use ($parentId) {
            return $record['parent_id'] === $parentId;
        });
        $result   = $fRecords->map(function ($record) {
            return [
                'id'    => $record['id'],
                'title' => $record['title'],
            ];
        });
        return $result->sortBy('listorder')->values()->all();
    }


}
