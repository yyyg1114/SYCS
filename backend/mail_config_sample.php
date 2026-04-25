<?php

/**
 * SMTP Mail Configuration (Sample)
 * 
 * このファイルを 'mail_config.php' としてコピーし、
 * お使いのメールサービス（Gmail, SendGrid等）に合わせて設定してください。
 * 'mail_config.php' は機密情報を含むため、Git等の構成管理には含めないでください。
 */
return [
    'host'       => 'smtp.gmail.com',         // SMTPサーバーのホスト名
    'auth'       => true,                     // SMTP認証を有効にするか
    'username'   => 'your-email@gmail.com',   // メールアドレス
    'password'   => 'your-app-password',      // アプリパスワード（通常のパスワードではなく）
    'secure'     => 'tls',                    // 暗号化方式 (tls または ssl)
    'port'       => 587,                      // ポート番号 (TLSは587, SSLは465が一般的)
    'from_email' => 'your-email@gmail.com',   // 送信元メールアドレス
    'from_name'  => 'SYCS Administrator',     // 送信者名
];
