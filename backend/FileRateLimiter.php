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

        // 1時間に1回、自動的にクリーンアップを実行
        $this->autoCleanup();
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
     * キャッシュの自動クリーンアップ（1時間ごとの間隔で実行）
     */
    private function autoCleanup()
    {
        $lastRunFile = $this->cacheDir . '/.last_cleanup';
        $now = time();
        $interval = 3600; // 1時間（3600秒）

        // 最後のクリーンアップから1時間経過していない場合はスキップ
        if (file_exists($lastRunFile)) {
            $lastRun = (int)file_get_contents($lastRunFile);
            if ($now - $lastRun < $interval) {
                return;
            }
        }

        // 次の並行処理を防ぐため、先に実行時間を更新
        file_put_contents($lastRunFile, $now);

        $this->cleanup();
    }

    /**
     * 古いキャッシュファイルをクリーンアップ（日次ローテーションとファイルサイズ制限付き）
     */
    public function cleanup()
    {
        $now = time();
        $files = glob($this->cacheDir . '/' . $this->prefix . '*.json');

        $totalSize = 0;
        $maxSizeBytes = 100 * 1024 * 1024; // 100MBの最大ファイルサイズ（ディレクトリ全体）制限
        $validFiles = [];

        if ($files) {
            foreach ($files as $file) {
                $fileMTime = filemtime($file);

                // 日次ローテーション：更新から24時間以上経過したファイルは無条件で削除
                if ($now - $fileMTime > 86400) {
                    @unlink($file);
                    continue;
                }

                $content = @file_get_contents($file);
                if ($content) {
                    $data = json_decode($content, true);
                    // 期限切れファイルの削除
                    if ($data && isset($data['reset_at']) && $data['reset_at'] < $now) {
                        @unlink($file);
                        continue;
                    }
                }

                // 削除されなかったファイルのサイズ計算
                $size = filesize($file);
                $totalSize += $size;
                $validFiles[] = [
                    'path' => $file,
                    'mtime' => $fileMTime,
                    'size' => $size
                ];
            }

            // 最大ファイルサイズ制限（100MB）を超過している場合、古い順にファイルを削除して80MB程度まで減らす
            if ($totalSize > $maxSizeBytes) {
                // 更新日時が古い順にソート（mtimeが小さい順）
                usort($validFiles, function ($a, $b) {
                    return $a['mtime'] <=> $b['mtime'];
                });

                $targetSizeBytes = $maxSizeBytes * 0.8; // 目標サイズ：80MB
                foreach ($validFiles as $info) {
                    if ($totalSize <= $targetSizeBytes) {
                        break;
                    }
                    @unlink($info['path']);
                    $totalSize -= $info['size'];
                }
            }
        }
    }
}
