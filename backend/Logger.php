<?php
// backend/Logger.php

class Logger
{
    private $logDir;

    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    public function __construct($logDir = null)
    {
        if ($logDir === null) {
            $logDir = dirname(__DIR__) . '/logs';
        }
        $this->logDir = $logDir;
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
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // 本番環境ではsyslogにも送信（環境変数はプロジェクトの構成に合わせて調整が必要な場合あり）
        if (getenv('APP_ENV') === 'production') {
            syslog(LOG_ERR, $logEntry);
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
