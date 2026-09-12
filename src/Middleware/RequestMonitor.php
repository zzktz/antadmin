<?php

namespace Antmin\Middleware;

use Closure;
use Antmin\Http\Services\RequestLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $queryLogging = (bool) (config('app.debug') && config('antmin.log_sql', false));
        if ($queryLogging) {
            DB::enableQueryLog();
        }

        # 执行请求
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            # 异常响应也要及时关闭查询日志，避免长驻进程持续累积 SQL。
            if ($queryLogging) {
                DB::disableQueryLog();
            }
            throw $e;
        }

        if ($response instanceof JsonResponse) {
            # 获取查询日志（在禁用之前）
            $queryLog = [];
            if ($queryLogging) {
                $queryLog = DB::getQueryLog();
                DB::disableQueryLog();
            }

            # 记录信息，假设我们将需要的信息写入队列
            $arr['uuid']             = $uuid;
            $arr['url']              = $url;
            $arr['method']           = $method;
            $arr['client']           = $request['reqClient'] ?? '';
            $arr['header']           = $header;
            $arr['params']           = $params;
            $arr['response_status']  = $response->getStatusCode();
            $arr['response_content'] = $response->getContent() ?? '';
            $arr['query_log']        = self::transformQueryLog($queryLog); # 添加查询日志到记录数据

            # 日志失败不应影响业务响应。
            try {
                RequestLogService::add($arr);
            } catch (\Throwable $e) {
                Log::warning('请求日志写入失败', ['error' => $e->getMessage()]);
            }

            # 在响应中添加额外参数
            $ins = ['reqUuid' => $uuid];
            $int = $response->getData(true); # 获取数组形式的数据
            $con = is_array($int) ? array_merge($ins, $int) : array_merge($ins, ['data' => $int]);
            # 保留原响应的状态码、响应头和 Cookie。
            $response->setData($con);
            return $response;
        }

        # 如果不是JsonResponse，也要禁用查询日志
        if ($queryLogging) {
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
