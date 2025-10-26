<?php

namespace Antmin\Middleware;

use Closure;
use Antmin\Http\Services\RequestLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RequestMonitor
{
    public static function handle($request, Closure $next)
    {
        # 获取请求的参数和地址
        $url    = $request->fullUrl();
        $params = $request->all();
        $method = $request->method();
        $header = $request->header();
        $uuid   = Str::uuid();

        # 在请求处理前启用查询日志
        if (config('app.debug')) {
            DB::enableQueryLog();
        }

        # 执行请求
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            # 获取查询日志（在禁用之前）
            $queryLog = [];
            if (config('app.debug')) {
                $queryLog = DB::getQueryLog();
                DB::disableQueryLog();
            }

            # 记录信息，假设我们将需要的信息写入队列
            $arr['uuid']             = $uuid;
            $arr['url']              = $url;
            $arr['method']           = $method;
            $arr['client']           = $request['ReqClient'] ?? '';
            $arr['header']           = $header;
            $arr['params']           = $params;
            $arr['response_status']  = $response->getStatusCode();
            $arr['response_content'] = $response->getContent() ?? '';
            $arr['query_log']        = self::transformQueryLog($queryLog); # 添加查询日志到记录数据

            # 入库
            RequestLogService::add($arr);

            # 在响应中添加额外参数
            $ins = ['request_uuid' => $uuid];
            $int = $response->getData(true); # 获取数组形式的数据
            $con = array_merge($ins, $int);
            return response()->json($con, $response->status());
        }

        # 如果不是JsonResponse，也要禁用查询日志
        if (config('app.debug')) {
            DB::disableQueryLog();
        }

        return $response;
    }


    protected static function transformQueryLog(array $queryLog): array
    {
        return array_map(function ($query) {
            return [
                'Query'    => $query['query'] ?? '',
                'Bindings' => $query['bindings'] ?? [],
                'Time'     => $query['time'] ?? 0,
            ];
        }, $queryLog);
    }

}