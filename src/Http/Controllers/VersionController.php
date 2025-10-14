<?php
/**
 * 版本升级
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\VersionService;
use Illuminate\Http\Request;

class VersionController extends BaseController
{

    /**
     * 版本升级 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        $action = $request['action'];
        if (method_exists(self::class, $action)) return self::$action($request);
        return errJson('No find action');
    }

    /**
     *【自动升级】检查是否有新的版本 每次刷新请求
     * @param  $request
     * @return mixed
     */
    protected function checkIsNewVersion($request)
    {
        $curVersionNo = Base::getValue($request, 'curVersionNo', '', 'max:50');
        $curVersionNo = $curVersionNo ?? '';
        $isNew        = VersionService::isHasNewVersion($curVersionNo);
        return Base::sucJson('成功', ['isNew' => $isNew]);
    }

    /**
     *【自动升级】更新版本 点击版本号进行弹窗更新
     * @param  $request
     * @return mixed
     */
    protected function updateVersion($request)
    {
        $curVersionNo = Base::getValue($request, 'curVersionNo', '', 'max:50');
        $curVersionNo = $curVersionNo ?? '';
        $method       = $request->method();
        if ($method == 'GET') {
            $res = VersionService::getLatestVersion($curVersionNo);
            return Base::sucJson('成功', $res);
        }
        VersionService::updateVersion($curVersionNo);
        sleep(2);
        return Base::sucJson('更新成功');
    }


}
