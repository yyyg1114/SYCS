<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/SecurityUtil.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/db.php';

ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
error_reporting(E_ALL);

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$validationError = SecurityUtil::validateSignupInput($username, $email, $password);
if ($validationError !== null) {
    http_response_code(400);
    die($validationError);
}

$username = trim($username);
$email = trim($email);
$encryptedEmail = SecurityUtil::encrypt($email);
$emailHash = hash('sha256', $email);
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$token = SecurityUtil::generateToken();

try {
    if (isset($mysqli)) {
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, email_hash, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $username, $encryptedEmail, $emailHash, $hashedPassword, $token);
        $inserted = $stmt->execute();
        if (!$inserted) {
            throw new \RuntimeException('Signup insert failed: ' . $stmt->error);
        }
    } elseif (isset($pdo)) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, email_hash, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $inserted = $stmt->execute([$username, $encryptedEmail, $emailHash, $hashedPassword, $token]);
        if (!$inserted) {
            throw new \RuntimeException('Signup insert failed');
        }
    } else {
        throw new \RuntimeException('Signup database connection is unavailable');
    }

    Mailer::sendVerification($email, $username, $token);
    header("Location: ../frontend/signup.php?pending=1");
    exit;
} catch (\Throwable $e) {
    error_log('Signup processing failed: ' . $e);
    http_response_code(500);
    echo "登録に失敗しました。";
}
