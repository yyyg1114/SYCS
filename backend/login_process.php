<?php
session_start();
require_once __DIR__ . '/db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    die("ユーザー名とパスワードを入力してください。");
}

$stmt = $mysqli->prepare("SELECT id, username, password, is_verified, last_thread_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    if ($user['is_verified'] == 0) {
        die("メールアドレスの本登録が完了していません。メールを確認してください。");
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user['username'];
    $_SESSION['last_thread_id'] = $user['last_thread_id'] ?: 1;
    
    header("Location: ../frontend/index.php");
    exit();
} else {
    die("ユーザー名またはパスワードが正しくありません。");
}
