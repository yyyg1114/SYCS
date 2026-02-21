<?php
require_once __DIR__ . '/EnvLoader.php';

// 環境変数が未設定の場合はエラーログを記録（フォールバックなし）
$mailHost      = getenv('MAIL_HOST');
$mailUser      = getenv('MAIL_USER');
$mailPass      = getenv('MAIL_PASS');
$mailFromEmail = getenv('MAIL_FROM_EMAIL');
$mailFromName  = getenv('MAIL_FROM_NAME');

if (!$mailHost || !$mailUser || $mailPass === false || !$mailFromEmail || !$mailFromName) {
    error_log("WARNING: One or more MAIL_* environment variables are not set.");
}

return [
    'host'       => $mailHost ?: null,
    'auth'       => true,
    'username'   => $mailUser ?: null,
    'password'   => $mailPass !== false ? $mailPass : null,
    'from_email' => $mailFromEmail ?: null,
    'from_name'  => $mailFromName ?: null,

    // 接続方式の優先順位（上から順に試行）
    'connections' => [
        ['secure' => 'tls', 'port' => 587], // TLS (推奨)
        ['secure' => 'ssl', 'port' => 465], // SSL (フォールバック)
    ],
];
