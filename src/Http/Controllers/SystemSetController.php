<?php

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\SystemSetService;


class SystemSetController extends BaseController
{


    /**
     * 设置 左侧小菜单
     * @return mixed
     */
    public static function systemSetMiniNav()
    {
        $res = SystemSetService::systemSetList(['is_show' => 1], 99);
        return Base::sucJson('成功', $res);
    }

    public static function systemSetList()
    {
        $res = SystemSetService::systemSetList([], 99);
        return Base::sucJson('成功', $res);
    }

    public static function systemSetAdd($request)
    {
        $title = Base::getValue($request, 'title', '', 'required|max:200');
        SystemSetService::systemSetAdd($title);
        return Base::sucJson('成功');
    }

    public static function systemSetEdit($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        if ($request->method() == 'GET') {
            $one = SystemSetService::systemSetDetail($id);
            return Base::sucJson('成功', $one);
        }
        $info['title'] = Base::getValue($request, 'title', '', 'required|max:200');
        SystemSetService::systemSetEdit($info, $id);
        return Base::sucJson('成功');
    }

    public static function systemSetDel($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        SystemSetService::systemSetDel($id);
        return Base::sucJson('成功');
    }

    public static function systemSetEditListorder($request)
    {
        $id                = Base::getValue($request, 'id', '', 'required|integer');
        $listorder         = Base::getValue($request, 'listorder', '', 'required|integer');
        $info['listorder'] = $listorder;
        SystemSetService::systemSetEdit($info, $id);
        return Base::sucJson('成功');
    }

    public static function systemSetEditIsShow($request)
    {
        $id  = Base::getValue($request, 'id', '', 'required|integer');
        $one = SystemSetService::systemSetDetail($id);
        if (empty($one)) {
            return Base::errJson('信息不存在');
        }
        $info['is_show'] = $one['is_show'] == 1 ? 0 : 1;
        SystemSetService::systemSetEdit($info, $id);
        return Base::sucJson('成功');
    }


    /**
     * 内容 GET POST
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailContent($request)
    {
        $method = $request->method();
        if ($method == 'GET') {
            $res = SystemSetService::oneDetailContentGet();
        } else {
            $data = Base::getValue($request, 'InfoData', '数据', 'array');
            SystemSetService::oneDetailContentPost($data);
        }
        return Base::sucJson('成功', $res ?? []);
    }

    /**
     * 配置 列表
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfig($request)
    {
        $search['name'] = Base::getValue($request, 'name', '', 'max:100');
        $res            = SystemSetService::oneDetailConfigList($search, 99);
        return Base::sucJson('成功', $res);
    }

    /**
     * 配置 添加
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigAdd($request)
    {
        if ($request->method() == 'GET') {
            $res['item'] = SystemSetService::systemSetItemTree();
            return Base::sucJson('成功', $res);
        }
        $info['title']     = Base::getValue($request, 'title', '标题', 'required|max:200');
        $info['flag']      = Base::getValue($request, 'flag', '提示', 'max:255');
        $info['item_type'] = Base::getValue($request, 'item_type', '', 'required|max:50');
        $tabdatas          = Base::getValue($request, 'tabdatas', '', 'array');
        $info['tip']       = Base::getValue($request, 'tip', '提示', 'max:200');
        $info['option']    = !empty($tabdatas) ? json_encode($tabdatas) : '';
        SystemSetService::oneDetailConfigAdd($info);
        return Base::sucJson('成功');
    }

    /**
     * 配置 编辑
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigEdit($request)
    {
        $id  = Base::getValue($request, 'id', '', 'required|integer');
        $one = SystemSetService::oneDetailConfigGetInfo($id);
        if (empty($one)) {
            return errJson('数据不存在');
        }
        if ($request->method() == 'GET') {
            $one['tabdatas'] = !empty($one['option']) ? json_decode($one['option'], true) : [];
            $one['item']     = SystemSetService::systemSetItemTree();
            return Base::sucJson('ok', $one);
        }
        $tabdatas            = Base::getValue($request, 'tabdatas', '', 'array');
        $info['title']       = Base::getValue($request, 'title', '标题', 'required|max:200');
        $info['item_type']   = Base::getValue($request, 'item_type', '', 'required|max:50');
        $info['is_required'] = Base::getValue($request, 'is_required', '', 'bool');
        $info['is_show']     = Base::getValue($request, 'is_show', '', 'bool');
        $info['tip']         = Base::getValue($request, 'tip', '提示', 'max:200');
        $info['option']      = !empty($tabdatas) ? json_encode($tabdatas) : [];

        SystemSetService::oneDetailConfigEdit($info, $id);
        return Base::sucJson('成功');
    }

    /**
     * 配置 删除
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigDel($request)
    {
        $id = Base::getValue($request, 'id', '', 'required|integer');
        SystemSetService::oneDetailConfigDel($id);
        return Base::sucJson('成功');
    }

    /**
     * 配置 编辑排序
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigEditListorder($request)
    {
        $id              = Base::getValue($request, 'id', '', 'required|integer');
        $up['listorder'] = Base::getValue($request, 'listorder', '提示', 'max:99999|integer');
        SystemSetService::oneDetailConfigEdit($up, $id);
        return Base::sucJson('成功');
    }

    /**
     * 配置 编辑提示语
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfiEditTip($request)
    {
        $id        = Base::getValue($request, 'id', '', 'required|integer');
        $up['tip'] = Base::getValue($request, 'tip', '提示', 'max:255');
        SystemSetService::oneDetailConfigEdit($up, $id);
        return Base::sucJson('成功');
    }

    /**
     * 配置 编辑是否显示开关
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigIsShowSwitch($request)
    {
        $id            = Base::getValue($request, 'id', 'ID', 'required|integer');
        $one           = SystemSetService::oneDetailConfigGetInfo($id);
        $up['is_show'] = $one['is_show'] == 1 ? 0 : 1;
        SystemSetService::oneDetailConfigEdit($up, $id);
        return Base::sucJson('成功');
    }

    /**
     * 配置 编辑是否必填项开关
     * @param  $request
     * @return mixed
     */
    public static function systemSetOneDetailConfigIsRequiredSwitch($request)
    {
        $id                = Base::getValue($request, 'id', 'ID', 'required|integer');
        $one               = SystemSetService::oneDetailConfigGetInfo($id);
        $up['is_required'] = $one['is_required'] == 1 ? 0 : 1;
        SystemSetService::oneDetailConfigEdit($up, $id);
        return Base::sucJson('成功');
    }


}
