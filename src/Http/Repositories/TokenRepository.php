<?php
/**
 * token
 */

namespace Antmin\Http\Repositories;

use Antmin\Exceptions\CommonException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Claims\Factory as ClaimFactory;
use Illuminate\Support\Facades\Redis;

class TokenRepository
{

    public static function getIdByToken(string $token): int
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $id      = $payload->get('sub');
            $arr     = $payload->toArray();
            $role    = $arr['role'];
            if (empty($arr['role'])) {
                throw new CommonException('Token 无角色设置');
            }
            if ($role != AccountRepository::$guardRole) {
                throw new CommonException('Token 非法角色');
            }
            return $id;
        } catch (TokenExpiredException $e) {
            throw new CommonException('Token 过期，请重新获取');
        } catch (TokenInvalidException $e) {
            throw new CommonException('Token 无效，请重新获取');
        } catch (JWTException $e) {
            throw new CommonException('Token 字段不存在');
        }
    }


    /**
     * 获取 token 自定义设置过期时间
     * @param int $accoutId
     * @return string
     */
    public static function getTokenById(int $accoutId): string
    {
        # 设置过期时间 分钟
        $expireTime = 60 * 24 * 30;
        $accoutInfo = AccountRepository::find($accoutId);

        # 2.0+ 新方式：创建Payload并生成token
        # 获取 claim factory 实例
        $claimFactory = app(ClaimFactory::class);

        # 构建基础声明（包含 sub, iat, jti 等）并合并自定义声明
        $claims = $claimFactory->make([
            'sub' => $accoutInfo->id, # 主题，通常是用户ID
            'ttl' => $expireTime
        ]);

        # 显式设置过期时间
        $claims->set('exp', now()->addMinutes($expireTime)->timestamp);

        # 生成 token
        $token = JWTAuth::encode($claims);
        self::saveTokens($token, $accoutId);
        return $token;
    }


    /**
     * 保存 token 如果是 SSO，需要作废所有现有的 token，并保存新的 token
     * @param string $token
     * @param int $id
     * @return void
     */
    private static function saveTokens(string $token, int $id): void
    {
        $key   = "account_tokens:" . $id;
        $redis = Redis::connection('default');

        # 一个用户 最大可以拥有token 数量
        $maxNum = 3;
        # 毫秒
        $milliseconds = intval(microtime(true) * 1000);
        $redis->zadd($key, $milliseconds, $token); # 有序集合
        $redis->expire($key, 86000 * 30);
        # 删除
        $hasMax = $redis->zcard($key); # 集合现有元素数量
        $forNum = $hasMax - $maxNum;   # 循环次数
        if ($forNum < 1) {
            return;
        }
        for ($i = 0; $i < $forNum; $i++) {
            $redis->zpopmin($key); # 删除分值最小的
        }
    }


}
