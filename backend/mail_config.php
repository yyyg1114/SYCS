<?php
/**
 * SMTP Mail Configuration
 * 
 * 下記の情報を、お使いのメールサービス（Gmail, SendGrid等）に合わせて設定してください。
 */
return [
    'host'       => 'smtp.gmail.com',         // SMTPサーバーのホスト名
    'auth'       => true,                     // SMTP認証を有効にするか
    'username'   => 'pkyg3328@gmail.com',   // メールアドレス
    'password'   => '',      // アプリパスワード（通常のパスワードではなく）
    'secure'     => 'tls',                    // 暗号化方式 (tls または ssl)
    'port'       => 587,                      // ポート番号 (TLSは587, SSLは465が一般的)
    'from_email' => 'pkyg3328@gmail.com',   // 送信元メールアドレス
    'from_name'  => 'SYCS Administrator',     // 送信者名
];
