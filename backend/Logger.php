<?php
// backend/Logger.php

class Logger
{
    private $logDir;

    /** @var int ログローテーションのファイルサイズ閾値（バイト）デフォルト: 5MB */
    private int $maxFileSize;

    /** @var int ローテーション後に保持する世代数 */
    private int $maxGenerations;

    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * @param string|null $logDir        ログディレクトリ（省略時は /logs）
     * @param int         $maxFileSize   ローテーション閾値（バイト）デフォルト: 5MB
     * @param int         $maxGenerations 保持する世代数 デフォルト: 5世代
     */
    public function __construct(
        $logDir = null,
        int $maxFileSize = 5 * 1024 * 1024,
        int $maxGenerations = 5
    ) {
        if ($logDir === null) {
            $logDir = dirname(__DIR__) . '/logs';
        }
        $this->logDir        = $logDir;
        $this->maxFileSize   = $maxFileSize;
        $this->maxGenerations = $maxGenerations;

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function log($level, $message, $context = [], $exception = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message";

        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        if ($exception) {
            $logEntry .= " | Exception: " . $exception->getMessage();
            $logEntry .= " | Stack: " . $exception->getTraceAsString();
        }

        $logEntry .= "\n";

        $logFile = $this->logDir . '/' . strtolower($level) . '.log';

        // 書き込み前にローテーションチェック
        $this->rotateIfNeeded($logFile);

        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // 本番環境ではsyslogにも送信（環境変数はプロジェクトの構成に合わせて調整が必要な場合あり）
        if (getenv('APP_ENV') === 'production') {
            syslog(LOG_ERR, $logEntry);
        }
    }

    /**
     * ログファイルのサイズが閾値を超えた場合にローテーションを実行する
     *
     * ローテーション後のファイル名: {name}.log.{YYYY-MM-DD}.{N}.bak
     * 例: error.log.2026-03-05.1.bak
     *
     * @param string $logFile ログファイルの絶対パス
     */
    public function rotateIfNeeded(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }

        if (filesize($logFile) < $this->maxFileSize) {
            return;
        }

        $this->rotate($logFile);
    }

    /**
     * ログファイルを強制的にローテーションする
     *
     * @param string $logFile ログファイルの絶対パス
     */
    public function rotate(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }

        $date = date('Y-m-d');
        $baseName = $logFile; // 例: /path/to/logs/error.log

        // 同日に複数回ローテーションが起きる場合の連番管理
        $n = 1;
        do {
            $backupFile = "{$baseName}.{$date}.{$n}.bak";
            $n++;
        } while (file_exists($backupFile));

        rename($logFile, $backupFile);

        // 古いバックアップを世代数に応じて削除
        $this->purgeOldBackups($logFile);
    }

    /**
     * 古いバックアップファイルを maxGenerations 以下に保つ
     *
     * @param string $logFile 元のログファイルパス
     */
    private function purgeOldBackups(string $logFile): void
    {
        $pattern = $logFile . '.*.bak';
        $backups = glob($pattern);

        if ($backups === false || count($backups) <= $this->maxGenerations) {
            return;
        }

        // 更新日時の古い順にソート
        usort($backups, fn($a, $b) => filemtime($a) <=> filemtime($b));

        $deleteCount = count($backups) - $this->maxGenerations;
        for ($i = 0; $i < $deleteCount; $i++) {
            if (file_exists($backups[$i])) {
                unlink($backups[$i]);
            }
        }
    }

    public function debug($message, $context = [])
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    public function info($message, $context = [])
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    public function warning($message, $context = [])
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    public function error($message, $context = [], $exception = null)
    {
        $this->log(self::LEVEL_ERROR, $message, $context, $exception);
    }

    public function critical($message, $context = [], $exception = null)
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context, $exception);
    }
}
