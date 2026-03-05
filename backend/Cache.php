<?php
// backend/Cache.php

/**
 * ファイルベースのキャッシュクラス
 *
 * backend/cache/ ディレクトリにJSONファイルとしてキャッシュを保存します。
 * 外部API呼び出しの結果等をTTL付きでキャッシュし、不要なリクエストを削減します。
 */
class Cache
{
    /** @var string キャッシュファイルの保存ディレクトリ */
    private string $cacheDir;

    /**
     * @param string|null $cacheDir キャッシュディレクトリのパス（省略時は backend/cache/）
     */
    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? dirname(__DIR__) . '/backend/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * キャッシュからデータを取得する
     *
     * @param string $key キャッシュキー
     * @return mixed TTL内であればキャッシュ値、期限切れ/未存在の場合は null
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['expires_at'], $data['value'])) {
            return null;
        }

        // TTL期限切れチェック
        if (time() > $data['expires_at']) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * キャッシュにデータを保存する
     *
     * @param string $key   キャッシュキー
     * @param mixed  $value 保存する値（JSONシリアライズ可能なもの）
     * @param int    $ttl   有効期限（秒）デフォルト: 300秒（5分）
     */
    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'key'        => $key,
            'value'      => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
        ];
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * 指定キーのキャッシュを削除する
     *
     * @param string $key キャッシュキー
     */
    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * キャッシュにデータが存在するか確認する（TTLチェック込み）
     *
     * @param string $key キャッシュキー
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * すべてのキャッシュを削除する
     */
    public function flush(): void
    {
        foreach (glob($this->cacheDir . '/*.cache.json') as $file) {
            @unlink($file);
        }
    }

    /**
     * 期限切れのキャッシュファイルを一括削除する（定期クリーンアップ用）
     *
     * @return int 削除したファイル数
     */
    public function purgeExpired(): int
    {
        $count = 0;
        foreach (glob($this->cacheDir . '/*.cache.json') as $file) {
            $data = json_decode(@file_get_contents($file), true);
            if (!$data || !isset($data['expires_at']) || time() > $data['expires_at']) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }

    /**
     * キャッシュキーからファイルパスを生成する
     *
     * @param string $key
     * @return string
     */
    private function getFilePath(string $key): string
    {
        // キーをSHA256でハッシュ化してファイル名に使用（特殊文字やパストラバーサル対策）
        $hash = hash('sha256', $key);
        return $this->cacheDir . '/' . $hash . '.cache.json';
    }
}
