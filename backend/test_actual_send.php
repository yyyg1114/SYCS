<?php
require_once __DIR__ . '/Mailer.php';

// テスト用の設定（このスクリプト内でのみ使用）
$testTo = 'pkyg3328@gmail.com'; // 自分のメールアドレスに変更してテストしてください

echo "Starting SMTP sending test to: $testTo...\n";

// Mailer::send は private なので、sendVerification を使ってテスト
$result = Mailer::sendVerification($testTo, 'TestUser', 'test-token-123');

if ($result) {
    echo "SUCCESS: Email sent successfully (check your inbox and logs/mail.log).\n";
} else {
    echo "FAILED: Email sending failed. Check PHP error logs or logs/mail.log for details.\n";
    // error_log に出力された内容はシステム管理者側で確認する必要があります
}
