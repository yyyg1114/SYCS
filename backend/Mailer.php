<?php
require_once __DIR__ . '/SecurityUtil.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';

class Mailer
{
    private static function send(string $to, string $subject, string $body): bool
    {
        // 設定ファイルの読み込み
        $configPath = __DIR__ . '/mail_config.php';
        if (!file_exists($configPath)) {
            error_log("Mailer Error: 'backend/mail_config.php' not found. Please create it from 'backend/mail_config_sample.php'.");
            return false;
        }
        $config = require $configPath;
        $from = $config['from_email'] ?? 'unknown';

        // 開発用ログ出力
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        $logEntry = "[" . date('Y-m-d H:i:s') . "] FROM: $from\nTO: $to\nSUBJECT: $subject\nBODY:\n$body\n" . str_repeat('-', 30) . "\n";
        file_put_contents("$logDir/mail.log", $logEntry, FILE_APPEND);

        // 設定が初期状態のままの場合は送信エラーとする
        if ($config['password'] === 'your-app-password') {
            error_log("Mailer Error: SMTP password is not configured in 'mail_config.php'.");
            return false;
        }

        // 接続設定の取得（新形式と旧形式の両方に対応）
        $connections = $config['connections'] ?? [
            ['secure' => $config['secure'] ?? 'tls', 'port' => $config['port'] ?? 587]
        ];

        $lastError = '';
        
        // 各接続設定を順番に試行
        foreach ($connections as $conn) {
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->CharSet = 'UTF-8';
                $mail->SMTPDebug = 0;                      // デバッグ出力を無効化（フォールバック時にログが増えるため）
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug: $str");
                };
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = $config['auth'];
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->SMTPSecure = $conn['secure'];
                $mail->Port       = $conn['port'];
                $mail->Timeout    = 10;                    // 接続タイムアウト（秒）

                //Recipients
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($to);

                //Content
                $mail->isHTML(false);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                
                // 成功時：使用した接続方式をログに記録
                error_log("Mailer: Email sent successfully via {$conn['secure']}:{$conn['port']}");
                return true;
                
            } catch (Exception $e) {
                $lastError = $mail->ErrorInfo;
                error_log("Mailer: Failed with {$conn['secure']}:{$conn['port']} - {$mail->ErrorInfo}");
                // 次の接続設定を試行
                continue;
            }
        }
        
        // すべての接続設定で失敗
        error_log("Mailer Error: All connection methods failed. Last error: $lastError");
        return false;
    }

    private static function getBaseUrl(): string
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // 現在の実行スクリプトのパスを取得
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptPath));
        
        // パスの正規化
        if ($dir === '.' || $dir === '/' || $dir === '\\') {
            $dir = '';
        } else {
            $dir = '/' . trim($dir, '/');
        }
        
        // backend ディレクトリ内から呼ばれた場合の調整
        if (strpos($dir, '/backend') !== false) {
            // backend の一つ上の階層を取得し、frontend を付与
            $basePath = substr($dir, 0, strpos($dir, '/backend'));
            $baseUrl = "$protocol://$host" . $basePath . "/frontend";
        } else {
            // frontend やルートから呼ばれた場合は、そのディレクトリをそのまま使う
            $baseUrl = "$protocol://$host" . $dir;
        }
        
        return rtrim($baseUrl, '/');
    }

    public static function sendVerification(string $toEmail, string $username, string $token): bool
    {
        $baseUrl = self::getBaseUrl();
        $url = "$baseUrl/verify.php?token=" . $token;
        $subject = "【SYCS】本登録を完了してください";
        $body = "こんにちは、{$username} 様。\n\nSYCS へのご登録ありがとうございます。\n以下のリンクをクリックして、本登録を完了してください。\n\n$url\n\nこのメールに心当たりがない場合は破棄してください。";
        
        return self::send($toEmail, $subject, $body);
    }

    public static function sendPasswordReset(string $toEmail, string $username, string $token): bool
    {
        $baseUrl = self::getBaseUrl();
        $url = "$baseUrl/reset_password.php?token=" . $token;
        $subject = "【SYCS】パスワードのリセット";
        $body = "こんにちは、{$username} 様。\n\nパスワードのリセットリクエストを受け付けました。\n新しいパスワードを設定するには、以下のリンクをクリックしてください。\n\n$url\n\n有効期限は1時間です。心当たりがない場合は、このメールを無視して構いません。パスワードは変更されません。";
        
        return self::send($toEmail, $subject, $body);
    }
}
