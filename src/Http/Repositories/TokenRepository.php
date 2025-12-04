<?php
/**
 * Token 相关仓储服务 (适配 Laravel-S / Swoole 环境)
 * 负责 Token 的生成、验证、存储与管理
 */

namespace Antmin\Http\Repositories;

use Antmin\Models\Account;
use Antmin\Exceptions\CommonException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Support\Facades\Log;

class TokenRepository
{
    private const TOKEN_EXPIRE_MINUTES = 60 * 24 * 30;
    private const MAX_TOKENS_PER_USER  = 3;
    private const REDIS_KEY_PREFIX     = 'account_tokens:';
    private const ROLE_NAME            = 'antadmin';

    /**
     * 构造函数注入依赖
     * @param Account $accountModel 账户模型实例
     * @param Connection $redisConnection Redis连接实例 (由容器注入默认连接)
     */
    public function __construct(
        protected Account    $accountModel,
        protected Connection $redisConnection # 注入 Redis 连接实例
    )
    {
        # Laravel 容器会自动解析并注入默认的 Redis 连接
    }

    /**
     * 由 token 解析并验证用户 ID
     * @param string $token
     * @return int
     * @throws CommonException
     */
    public function getIdByToken(string $token): int
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $id      = $payload->get('sub');
            $role    = $payload->get(self::ROLE_NAME, '');

            if (empty($role)) {
                throw new CommonException('Token 无角色设置');
            }
            if ($role != self::ROLE_NAME) {
                throw new CommonException('Token 非法角色');
            }

            if (!$this->isTokenExists($token, $id)) {
                throw new CommonException('超过终端最大许可数，设备下线。');
            }
            return $id;
        } catch (TokenExpiredException $e) {
            throw new CommonException('Token 过期，请重新获取');
        } catch (TokenInvalidException $e) {
            throw new CommonException('Token 无效，请重新获取');
        } catch (JWTException $e) {
            Log::warning('JWT解析异常', ['exception' => $e->getMessage()]);
            throw new CommonException('Token 字段不存在或格式错误');
        }
    }

    /**
     * 根据账户ID生成JWT Token
     * @param int $accountId
     * @return string
     * @throws CommonException
     */
    public function getTokenById(int $accountId): string
    {
        $accountInfo = $this->accountModel->find($accountId);
        if (!$accountInfo) {
            throw new CommonException('账户不存在，无法生成Token');
        }

        $customClaims = [
            'exp'           => now()->addMinutes(self::TOKEN_EXPIRE_MINUTES)->timestamp,
            'ttl'           => self::TOKEN_EXPIRE_MINUTES,
            self::ROLE_NAME => self::ROLE_NAME,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($accountInfo);
        $this->saveTokens($token, $accountId);
        return $token;
    }

    /**
     * 保存token到Redis（先进先出淘汰机制）
     * @param string $token
     * @param int $id
     * @return void
     */
    private function saveTokens(string $token, int $id): void
    {
        $key = self::REDIS_KEY_PREFIX . $id;
        # 使用注入的 redisConnection 实例
        $redis = $this->redisConnection;

        # 使用流水线
        $redis->pipeline(function ($pipe) use ($key, $token) {
            $milliseconds = intval(microtime(true) * 1000);
            $pipe->zadd($key, $milliseconds, $token);
            $pipe->expire($key, 60 * 60 * 24 * 30);
        });

        # 原子化删除旧Token
        $removeEndIndex = -(self::MAX_TOKENS_PER_USER + 1);
        $redis->zremrangebyrank($key, 0, $removeEndIndex);
    }

    /**
     * 判断指定用户的Token是否在有效集合中
     * @param string $token
     * @param int $id
     * @return bool
     */
    private function isTokenExists(string $token, int $id): bool
    {
        $key = self::REDIS_KEY_PREFIX . $id;
        # 使用注入的 redisConnection 实例
        $score = $this->redisConnection->zscore($key, $token);
        return $score !== null;
    }
}
