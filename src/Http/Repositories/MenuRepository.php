<?php
/**
 * 菜单
 */

namespace Antmin\Http\Repositories;

use Antmin\Models\Menu;

class MenuRepository
{
    public function __construct(
        protected Menu $menuModel,
    )
    {

    }


    public function add(array $info): int
    {
        return $this->menuModel->create($info)->id;
    }

    public function edit(array $info, int $id): bool
    {
        return $this->menuModel->find($id)->update($info);
    }

    public function del(int $id): bool
    {
        return $this->menuModel->find($id)->delete();
    }

    public function getInfo(int $id): array
    {
        $one = $this->menuModel->find($id);
        return $one ? $one->toArray() : [];
    }

    public function getAllCacheData()
    {
        return $this->menuModel->getAllCacheData();
    }

    public function getDataByParentId(int $parentId): array
    {
        $allData  = $this->menuModel->getAllCacheData();
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
