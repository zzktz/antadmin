<?php


namespace Antmin\Middleware;

use Closure;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;

class Middleware
{

    /**
     * 中间件操作
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws CommonException
     */
    public static function handle($request, Closure $next)
    {
        $path            = $request->path();
        $method          = self::getMethodName($path);
        $token           = $request->header('Access-Token');
        $request['page'] = $request['pageNo'] ?? 1;
        # 过滤
        if (in_array($method, Filter::getFilterMethod())) {
            return $next($request);
        }

        if (empty($token)) {
            throw new CommonException('Access-Token 不存在');
        }

        $request['accountId'] = AccountService::getAccountIdByToken($token);
        return $next($request);
    }

    /**
     * 方法名称
     * @param string $path
     * @return string
     */
    private static function getMethodName(string $path): string
    {
        # 如果字符串以 'api' 开头，按 '/' 分割并获取第二部分
        $parts = explode('/', $path);
        if (str_starts_with($path, 'api')) {
            $result = $parts[2] ?? '';
        }
        if (empty($result)) {
            throw new CommonException('请求路径非法');
        }
        return $result;
    }

}