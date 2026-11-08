<?php

class FileRateLimiter
{
    private $cacheDir;
    private $prefix = 'rate_limit_';

    /**
     * コンストラクタ
     * @param string $cacheDir キャッシュディレクトリ
     */
    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sycs_rate_limit_cache';

        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new \RuntimeException('Unable to create rate-limit cache directory');
            }
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
    public function checkUserRateLimit($userId, $maxRequests = 12, $windowSeconds = 60, $scope = '')
    {
        $scope = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$scope);
        $key = 'user_' . (int)$userId . ($scope !== '' ? '_' . $scope : '');
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
        $key = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$key);
        $file = $this->cacheDir . DIRECTORY_SEPARATOR . $this->prefix . $key . '.json';
        $now = time();
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open rate-limit cache file');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock rate-limit cache file');
            }

            $raw = stream_get_contents($handle);
            if ($raw === false) {
                throw new \RuntimeException('Unable to read rate-limit cache file');
            }
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['count'], $data['reset_at']) && $data['reset_at'] > $now) {
                $current = (int)$data['count'] + 1;
                $resetAt = (int)$data['reset_at'];
            } else {
                $current = 1;
                $resetAt = $now + $windowSeconds;
            }

            $payload = json_encode([
                'count' => $current,
                'reset_at' => $resetAt,
                'last_update' => $now,
            ]);
            if (
                $payload === false ||
                !ftruncate($handle, 0) ||
                !rewind($handle) ||
                fwrite($handle, $payload) !== strlen($payload) ||
                !fflush($handle)
            ) {
                throw new \RuntimeException('Unable to write rate-limit cache file');
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

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
        $lastRunFile = $this->cacheDir . DIRECTORY_SEPARATOR . '.last_cleanup';
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
        $files = glob($this->cacheDir . DIRECTORY_SEPARATOR . $this->prefix . '*.json');

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
