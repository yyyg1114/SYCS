<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/EnvLoader.php';
require_once 'SecurityUtil.php';
require_once 'Mailer.php';

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$email || !$password) {
    die("入力項目が足りません");
}

$encryptedEmail = SecurityUtil::encrypt($email);
$emailHash = hash('sha256', $email);
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$token = SecurityUtil::generateToken();

// Note: Using $mysqli for consistency with index.php if possible, but the original used $pdo.
// Let's check db.php content to see which one is defined.
require 'db.php';
// If db.php defines $mysqli, we should use it. If it defines $pdo, we use that.
// Earlier I saw db.php uses mysqli. Let's adapt if needed.

if (isset($mysqli)) {
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, email_hash, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("sssss", $username, $encryptedEmail, $emailHash, $hashedPassword, $token);
    if ($stmt->execute()) {
        Mailer::sendVerification($email, $username, $token);
        header("Location: ../frontend/signup.php?pending=1");
    } else {
        echo "登録失敗: " . $mysqli->error;
    }
} else if (isset($pdo)) {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, email_hash, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
    if ($stmt->execute([$username, $encryptedEmail, $emailHash, $hashedPassword, $token])) {
        Mailer::sendVerification($email, $username, $token);
        header("Location: ../frontend/signup.php?pending=1");
    } else {
        echo "登録失敗";
    }
}
