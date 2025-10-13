<?php
/**
 * 选项
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\ItemService;
use Illuminate\Http\Request;


class ItemController extends BaseController
{

    /**
     * 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        $action               = $request['action'];
        $request['accountId'] = AccountService::getAccountIdByToken();
        unset($request['action']);
        if (method_exists(self::class, $action)) return self::$action($request);
        return errJson('No find action');
    }

    /**
     * 列表
     * @return mixed
     */
    public static function index($request)
    {
        $limit  = Base::getValue($request, 'pageSize', '', 'integer');
        $limit  = $limit ?? 10;
        $search = [];
        $res    = ItemService::getList($limit, $search);
        return Base::sucJson('成功', $res);
    }

    /**
     * 添加
     * @param $request
     * @return mixed
     */
    public static function add($request)
    {
        $flag          = Base::getValue($request, 'flag', '', 'required|max:64');
        $info['title'] = Base::getValue($request, 'title', '', 'required|max:128');
        $info['flag']  = strtoupper($flag);
        ItemService::add($info);
        return Base::sucJson('成功');
    }

    /**
     * 删除
     * @param $request
     * @return mixed
     */
    public static function dels($request)
    {
        $ids = Base::getValue($request, 'ids', '', 'required|array');
        ItemService::dels($ids);
        return Base::sucJson('成功');
    }

    /**
     * 编辑标题
     * @param $request
     * @return mixed
     */
    public static function editTitle($request)
    {
        $id    = Base::getValue($request, 'id', '', 'required|integer');
        $title = Base::getValue($request, 'title', '', 'required|max:128');
        ItemService::editTitle($title, $id);
        return Base::sucJson('成功');
    }


    /**
     * 值列表
     * @param $request
     * @return mixed
     */
    public static function detailList($request)
    {
        $limit  = Base::getValue($request, 'pageSize', '', 'integer');
        $limit  = $limit ?? 10;
        $itemId = Base::getValue($request, 'id', '', 'required|integer');
        $res    = ItemService::getDetailList($limit, $itemId);
        return Base::sucJson('成功', $res);
    }

    /**
     * 值添加
     * @param $request
     * @return mixed
     */
    public static function detailAdd($request)
    {
        $itemId = Base::getValue($request, 'id', '', 'integer');
        $value  = Base::getValue($request, 'value', '', 'required|max:999');
        $flag   = Base::getValue($request, 'flag', '', 'alpha_num|max:64');
        $flag   = strtoupper($flag);
        ItemService::detailAdd($value, $flag, $itemId);
        return Base::sucJson('成功');
    }

    /**
     * 值编辑
     * @param $request
     * @return mixed
     */
    public static function detailEditValue($request)
    {
        $id    = Base::getValue($request, 'id', '', 'required|integer');
        $value = Base::getValue($request, 'value', '', 'required|max:999');
        ItemService::editValue($value, $id);
        return Base::sucJson('成功');
    }

    /**
     * 排序编辑
     * @param $request
     * @return mixed
     */
    public static function detailEditListorder($request)
    {
        $id        = Base::getValue($request, 'id', '', 'required|integer');
        $listorder = Base::getValue($request, 'listorder', '', 'required|integer|max:99999|min:0');
        ItemService::editListorder($listorder, $id);
        return Base::sucJson('成功');
    }

    /**
     * 状态编辑
     * @param $request
     * @return mixed
     */
    public static function detailEditStatus($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        ItemService::editStatus($id);
        return Base::sucJson('成功');
    }

    /**
     * 项值删除
     * @param $request
     * @return mixed
     */
    public static function detailDel($request)
    {
        $ids = Base::getValue($request, 'ids', '', 'required|array');
        ItemService::detailDels($ids);
        return Base::sucJson('成功');
    }
}
