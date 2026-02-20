<?php
require_once __DIR__ . '/EnvLoader.php';

return [
    'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'auth' => true,
    'username' => getenv('MAIL_USER') ?: 'information.sycs@gmail.com',
    'password' => getenv('MAIL_PASS') ?: '',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'information.sycs@gmail.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'SYCS Administrator',

    // 接続方式の優先順位（上から順に試行）
    'connections' => [
        ['secure' => 'tls', 'port' => 587], // TLS (推奨)
        ['secure' => 'ssl', 'port' => 465], // SSL (フォールバック)
    ],
];
