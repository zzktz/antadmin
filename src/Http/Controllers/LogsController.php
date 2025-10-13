<?php
/**
 * 系统日志
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\AccountService;
use Antmin\Tool\CacheTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LogsController extends BaseController
{

    /**
     * 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        $request['accountId'] = AccountService::getAccountIdByToken();
        if ($request['accountId'] != 1) {
            return Base::sucJson('无权操作');
        }
        $action = Base::getValue($request, 'action', '', 'required|max:50');
        if (method_exists(self::class, $action)) return self::$action($request);
        return errJson('No find action');
    }

    /**
     * 读取内容
     * @return mixed
     */
    public static function index($request)
    {
        $type = Base::getValue($request, 'type', '', 'required|max:50');
        if ($type == 'sql') {
            $path = 'logs/sqllog.log';
        } else {
            $path = 'logs/laravel.log';
        }
        $str            = Storage::disk('storage')->get($path);
        $content        = nl2br($str);
        $res['content'] = $content;
        return Base::sucJson('成功', $res);
    }

    /**
     * 清空内容
     * @return mixed
     */
    public static function clear($request)
    {
        $type = Base::getValue($request, 'type', '', 'required|max:50');
        if ($type == 'sql') {
            $path = 'logs/sqllog.log';
        } else {
            $path = 'logs/laravel.log';
        }
        $stor = Storage::disk('storage');
        if ($stor->exists($path)) {
            File::put($stor->path($path), '上次清空:' . date('Y-m-d H:i:s') . PHP_EOL);
        } else {
            return errJson('不存在');
        }
        return Base::sucJson('成功');
    }

    /**
     * 全部清空Model数据Redis缓存
     * @param $request
     * @return mixed
     */
    public static function clearCache($request)
    {
        CacheTool::clearDatabase();
        return Base::sucJson('成功');
    }


}
