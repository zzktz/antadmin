<?php
/**
 * 系统日志
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Tool\CacheTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LogsController extends BaseController
{

    /**
     * 日志入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        if ($request['accountId'] != 1) {
            return Base::errJson('无权操作');
        }
        $action = $request['action'];
        if (method_exists(self::class, $action)) return self::$action($request);
        return Base::errJson('No find action');
    }

    /**
     * 读取内容
     * @return mixed
     */
    public static function index($request)
    {
        $type    = Base::getValue($request, 'type', '', 'required|max:50');
        $path    = self::getLogPathByType($type);
        $string  = Storage::disk('storage')->get($path);
        $content = nl2br($string);
        return Base::sucJson('成功', ['content' => $content]);
    }

    /**
     * 清空内容
     * @return mixed
     */
    public static function clear($request)
    {
        $type = Base::getValue($request, 'type', '', 'required|max:50');
        $path = self::getLogPathByType($type);
        $stor = Storage::disk('storage');
        if ($stor->exists($path)) {
            File::put($stor->path($path), '上次清空:' . date('Y-m-d H:i:s') . PHP_EOL);
        } else {
            return Base::errJson('不存在');
        }
        return Base::sucJson('成功');
    }


    /**
     * 全部清空Model数据Redis缓存
     * @return mixed
     */
    public static function clearCache()
    {
        CacheTool::clearDatabase();
        return Base::sucJson('成功');
    }

    /**
     * 日志类型
     * @param string $type
     * @return string
     */
    private static function getLogPathByType(string $type): string
    {
        $arr = [
            'sql'     => 'logs/sqllog.log',
            'laravel' => 'logs/laravel.log',
        ];
        return $arr[$type] ?? $arr['laravel'];
    }
    

}
