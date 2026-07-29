<?php
// backend/google_config.php

if (file_exists(__DIR__ . '/google_config_local.php')) {
    include_once __DIR__ . '/google_config_local.php';
} else {
    require_once __DIR__ . '/EnvLoader.php';
    if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
    if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
}

if (!function_exists('getGoogleRedirectUri')) {
    /**
     * Google OAuth のリダイレクト URI を取得します。
     *
     * @param array|null $server $_SERVER の代替データ（テスト用）
     * @return string
     */
    function getGoogleRedirectUri(?array $server = null): string
    {
        $envUri = getenv('GOOGLE_REDIRECT_URI');
        if ($envUri !== false && $envUri !== '') {
            return $envUri;
        }

        $server ??= $_SERVER;

        $isHttps = (isset($server['HTTPS']) && $server['HTTPS'] === 'on') ||
            (isset($server['HTTP_X_FORWARDED_PROTO']) && $server['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $isHttps ? "https" : "http";

        $host = $server['HTTP_HOST'] ?? 'localhost';
        $path = rtrim(dirname($server['SCRIPT_NAME'] ?? ''), '/\\');
        return "$protocol://$host$path/login.php?api=google_callback";
    }
}
