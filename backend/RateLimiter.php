<?php

class RateLimiter
{
    private $redis;
    private $prefix = 'rate_limit:';

    /**
     * コンストラクタ
     * @param \Redis $redis Redis インスタンス
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * ユーザーのレート制限をチェック
     * @param int $userId ユーザーID
     * @param int $maxRequests 許可する最大リクエスト数
     * @param int $windowSeconds 時間枠（秒）
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public function checkUserRateLimit($userId, $maxRequests = 12, $windowSeconds = 60)
    {
        $key = $this->prefix . 'user:' . $userId;
        return $this->checkRateLimit($key, $maxRequests, $windowSeconds);
    }

    /**
     * IP のレート制限をチェック
     * @param string $ip IPアドレス
     * @param int $maxRequests 許可する最大リクエスト数
     * @param int $windowSeconds 時間枠（秒）
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public function checkIPRateLimit($ip, $maxRequests = 100, $windowSeconds = 60)
    {
        $key = $this->prefix . 'ip:' . $ip;
        return $this->checkRateLimit($key, $maxRequests, $windowSeconds);
    }

    /**
     * 汎用レート制限チェック
     * @param string $key レート制限キー
     * @param int $maxRequests 許可する最大リクエスト数
     * @param int $windowSeconds 時間枠（秒）
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    private function checkRateLimit($key, $maxRequests, $windowSeconds)
    {
        try {
            $current = $this->redis->incr($key);

            // 初回のみ有効期限を設定
            if ($current === 1) {
                $this->redis->expire($key, $windowSeconds);
            }

            $ttl = $this->redis->ttl($key);
            $resetAt = time() + $ttl;

            if ($current > $maxRequests) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_at' => $resetAt,
                    'retry_after' => $ttl,
                ];
            }

            return [
                'allowed' => true,
                'remaining' => $maxRequests - $current,
                'reset_at' => $resetAt,
                'retry_after' => 0,
            ];
        } catch (Exception $e) {
            // Redis 接続エラー時はリクエストを許可（フェイルオープン）
            error_log("RateLimiter Error: " . $e->getMessage());
            return [
                'allowed' => true,
                'remaining' => $maxRequests,
                'reset_at' => time() + $windowSeconds,
                'retry_after' => 0,
            ];
        }
    }

    /**
     * 特定のキーをリセット
     * @param string $key レート制限キー
     */
    public function reset($key)
    {
        $this->redis->del($this->prefix . $key);
    }
}
