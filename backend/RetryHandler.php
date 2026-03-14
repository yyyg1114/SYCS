<?php
// backend/RetryHandler.php

/**
 * 指数バックオフ付きリトライハンドラ
 *
 * 外部API呼び出し等の一時的な失敗を自動でリトライします。
 * 各試行の間に指数バックオフ（ディレイ倍増）を挟み、
 * サーバー負荷を軽減しながら回復を待ちます。
 */
class RetryHandler
{
    /**
     * コールバックをリトライ付きで実行する
     *
     * @param callable $fn          実行するコールバック（失敗時は例外を投げるか、null/falseを返す）
     * @param int      $maxRetries  最大リトライ回数（デフォルト: 3回）
     * @param int      $baseDelayMs 初回リトライ前の待機時間（ミリ秒）デフォルト: 500ms
     * @param array    $context     ログ用の追加コンテキスト情報
     * @return mixed コールバックの戻り値
     * @throws \Throwable 全試行失敗時は最後の例外を再スロー
     * @throws \RuntimeException 例外なしで null/false が返り続けた場合
     */
    public static function execute(
        callable $fn,
        int $maxRetries = 3,
        int $baseDelayMs = 500,
        array $context = []
    ): mixed {
        $lastException = null;
        $totalAttempts = $maxRetries + 1; // 初回 + リトライ回数

        for ($attempt = 1; $attempt <= $totalAttempts; $attempt++) {
            try {
                $result = $fn();

                // 戻り値が null または false の場合も失敗とみなす
                if ($result === null || $result === false) {
                    throw new \RuntimeException(
                        "RetryHandler: コールバックが null/false を返しました（試行 {$attempt}/{$totalAttempts}）"
                    );
                }

                // 成功
                if ($attempt > 1) {
                    error_log("RetryHandler: {$attempt}回目の試行で成功しました。" . self::formatContext($context));
                }
                return $result;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < $totalAttempts) {
                    // 指数バックオフ: 500ms → 1000ms → 2000ms ...
                    $delayMs = $baseDelayMs * (2 ** ($attempt - 1));
                    error_log(
                        "RetryHandler: 試行 {$attempt}/{$totalAttempts} 失敗 - " .
                        $e->getMessage() .
                        " | {$delayMs}ms 後にリトライします。" .
                        self::formatContext($context)
                    );
                    usleep($delayMs * 1000);
                } else {
                    error_log(
                        "RetryHandler: 全 {$totalAttempts} 回の試行が失敗しました - " .
                        $e->getMessage() .
                        self::formatContext($context)
                    );
                }
            }
        }

        // 全試行失敗 → 最後の例外を再スロー
        throw $lastException;
    }

    /**
     * リトライ付きでcurl実行する便利メソッド
     *
     * @param resource|\CurlHandle $ch         curl ハンドル（設定済み）
     * @param int                  $maxRetries 最大リトライ回数
     * @param array                $context    ログ用コンテキスト
     * @return string curlのレスポンス文字列
     * @throws \RuntimeException curl失敗時
     */
    public static function curlExec(
        $ch,
        int $maxRetries = 3,
        array $context = []
    ): string {
        return self::execute(
            function () use ($ch) {
                $response = curl_exec($ch);

                if ($response === false) {
                    $error = curl_error($ch);
                    $errno = curl_errno($ch);
                    throw new \RuntimeException("curl エラー [{$errno}]: {$error}");
                }

                // HTTP ステータスコードが 5xx の場合もリトライ対象とする
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode >= 500) {
                    throw new \RuntimeException(
                        "HTTP {$httpCode} サーバーエラー。レスポンス: " . substr($response, 0, 200)
                    );
                }

                return $response;
            },
            $maxRetries,
            500,
            $context
        );
    }

    /**
     * JSON APIをリトライ付きで呼び出す
     *
     * @param string $url          APIのURL
     * @param string $method       HTTPメソッド ('GET' or 'POST')
     * @param array  $params       リクエストパラメータ (GETはクエリ、POSTはbody)
     * @param array  $headers      追加のHTTPヘッダー
     * @param array  $retryContext ログ用コンテキスト
     * @return array|null デコードされたJSONデータ。失敗時は例外をスロー（executeによってリトライされる）
     * @throws \RuntimeException 通信エラー、HTTPエラー、またはJSONデコードエラー時
     */
    public static function callJsonApi(
        string $url,
        string $method = 'GET',
        array $params = [],
        array $headers = [],
        array $retryContext = []
    ): ?array {
        $maxRetries = 3;
        $baseDelayMs = 500;

        return self::execute(function () use ($url, $method, $params, $headers, $retryContext) {
            $ch = curl_init();
            
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                $queryUrl = $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
                curl_setopt($ch, CURLOPT_URL, $queryUrl);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                throw new \RuntimeException("curl エラー [{$errno}]: {$error}");
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 500) {
                throw new \RuntimeException("HTTP {$httpCode} サーバーエラー");
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("JSONデコードエラー: " . json_last_error_msg());
            }

            // API固有のエラーチェック (個別のAPIクラスで処理してもよいが、ここで共通的なエラーを拾う)
            if ($httpCode >= 400) {
                $errorMsg = $decoded['error'] ?? $decoded['message'] ?? $decoded['error_description'] ?? '不明なエラー';
                throw new \RuntimeException("HTTP {$httpCode} エラー: {$errorMsg}");
            }

            return $decoded;
        }, $maxRetries, $baseDelayMs, $retryContext);
    }

    /**
     * コンテキスト情報を文字列にフォーマットする（ログ用）
     *
     * @param array $context
     * @return string
     */
    private static function formatContext(array $context): string
    {
        if (empty($context)) {
            return '';
        }
        return ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
}
