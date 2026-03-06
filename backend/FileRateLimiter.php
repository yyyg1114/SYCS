<?php

class FileRateLimiter
{
    private $cacheDir;
    private $prefix = 'rate_limit_';

    /**
     * コンストラクタ
     * @param string $cacheDir キャッシュディレクトリ
     */
    public function __construct($cacheDir = '/tmp/rate_limit_cache')
    {
        $this->cacheDir = $cacheDir;

        // キャッシュディレクトリが存在しない場合は作成
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
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
        $key = 'user_' . $userId;
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
        $key = 'ip_' . str_replace('.', '_', $ip);
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
        $file = $this->cacheDir . '/' . $this->prefix . $key . '.json';
        $now = time();

        // ファイルが存在し、有効期限内の場合
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);

            // 有効期限チェック
            // 注意: json_decode() が false を返すかもしれない場合のエラーを避ける
            if ($data && isset($data['reset_at']) && $data['reset_at'] > $now) {
                $current = $data['count'] + 1;
                $resetAt = $data['reset_at'];
            } else {
                // 有効期限切れ、リセット
                $current = 1;
                $resetAt = $now + $windowSeconds;
            }
        } else {
            // 初回
            $current = 1;
            $resetAt = $now + $windowSeconds;
        }

        // ファイルに保存
        $data = [
            'count' => $current,
            'reset_at' => $resetAt,
            'last_update' => $now,
        ];
        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($current > $maxRequests) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $resetAt,
                'retry_after' => $resetAt - $now,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxRequests - $current,
            'reset_at' => $resetAt,
            'retry_after' => 0,
        ];
    }

    /**
     * 古いキャッシュファイルをクリーンアップ
     */
    public function cleanup()
    {
        $now = time();
        $files = glob($this->cacheDir . '/' . $this->prefix . '*.json');

        if ($files) {
            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content) {
                    $data = json_decode($content, true);
                    if ($data && isset($data['reset_at']) && $data['reset_at'] < $now) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}
