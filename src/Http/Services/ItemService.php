<?php
/**
 * 选项
 */

namespace Antmin\Http\Services;

use Antmin\Http\Repositories\ItemRepository;
use Antmin\Http\Repositories\ItemDetailRepository;
use Antmin\Exceptions\CommonException;

class ItemService
{
    /**
     * 根据标识获取全部项信息
     * @param string $flag
     * @return array
     */
    public static function getDataByFlag(string $flag): array
    {
        $flag = strtoupper($flag);
        $one  = ItemRepository::where('flag', $flag)->get()->first();
        if (empty($one)) {
            return [];
        }
        $res           = $one->toArray();
        $res['detail'] = ItemDetailRepository::where('item_id', $one['id'])->where('is_open', 1)
            ->orderBy('listorder', 'asc')->get(['id', 'value', 'flag'])->toArray();
        return $res;
    }

    /**
     * 项列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getList(int $limit, array $search): array
    {
        return ItemRepository::getList($limit, $search);
    }

    /**
     * 项添加
     * @param array $info
     * @return int
     */
    public static function add(array $info): int
    {
        $flag = $info['flag'];
        $one  = ItemRepository::where('flag', $flag)->get()->first();
        if (!empty($one)) {
            throw new CommonException('标识字段已存在');
        }
        return ItemRepository::add($info);
    }

    /**
     * 项标题编辑
     * @param string $title
     * @param int $id
     * @return bool
     */
    public static function editTitle(string $title, int $id): bool
    {
        ItemRepository::where('id', $id)->update(['title' => $title]);
        return true;
    }

    /**
     * 项删除
     * @param array $itemIds
     * @return bool
     */
    public static function dels(array $itemIds): bool
    {
        if (empty($itemIds)) {
            throw new CommonException('请选择数据');
        }
        $datas = ItemDetailRepository::whereIn('item_id', $itemIds)->get(['id'])->toArray();
        if (!empty($datas)) {
            throw new CommonException('所选项有项值不能删除');
        }
        ItemRepository::whereIn('id', $itemIds)->delete();
        return true;
    }

    /**
     * 值列表
     * @param int $limit
     * @param array $search
     * @return array
     */
    public static function getDetailList(int $limit, int $itemId): array
    {
        return ItemDetailRepository::getList($limit, $itemId);
    }

    /**
     * 值添加
     * @param string $value
     * @param string $flag
     * @param int $itemId
     * @return int
     */
    public static function detailAdd(string $value, string $flag, int $itemId): int
    {
        if (!empty($flag)) {
            $one = ItemDetailRepository::where('item_id', $itemId)->where('flag', $flag)->get()->first();
            if (!empty($one)) {
                throw new CommonException('该项标识已存在');
            }
        }
        $one = ItemDetailRepository::where('item_id', $itemId)->where('value', $value)->get()->first();
        if (!empty($one)) {
            throw new CommonException('该项值已存在');
        }
        return ItemDetailRepository::add($value, $flag, $itemId);
    }

    /**
     * 值编辑
     * @param string $value
     * @param int $id
     * @return bool
     */
    public static function editValue(string $value, int $id): bool
    {
        $info = ItemDetailRepository::getInfo($id);
        if (empty($info)) {
            throw new CommonException('项值不存在');
        }
        $one = ItemDetailRepository::where('item_id', $info['item_id'])->where('value', $value)->where('id', '!=', $id)->get()->first();
        if (!empty($one)) {
            throw new CommonException('项值命名已存在');
        }
        return ItemDetailRepository::where('id', $id)->update(['value' => $value]);
    }

    /**
     * 值排序编辑
     * @param int $listorder
     * @param int $id
     * @return bool
     */
    public static function editListorder(int $listorder, int $id): bool
    {
        return ItemDetailRepository::where('id', $id)->update(['listorder' => $listorder]);
    }

    /**
     * 值编辑状态
     * @param int $isOpen
     * @param int $id
     * @return bool
     */
    public static function editStatus(int $id): bool
    {
        $info   = ItemDetailRepository::getInfo($id);
        $isOpen = empty($info['is_open']) ? 1 : 0;
        return ItemDetailRepository::where('id', $id)->update(['is_open' => $isOpen]);
    }

    /**
     * 值删除
     * @param array $ids
     * @return bool
     */
    public static function detailDels(array $ids): bool
    {
        return ItemDetailRepository::whereIn('id', $ids)->delete();
    }


}
