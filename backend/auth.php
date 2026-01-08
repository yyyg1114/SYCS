<?php
session_start();
require 'db.php';

/**
 * ユーザーがログインしているかをチェック
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * ログイン必須ページで呼ぶ
 * ログインしていなければ login.php にリダイレクト
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../frontend/login.php');
        exit();
    }
}

/**
 * 現在のログインユーザー情報を取得
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ログアウト処理
 */
function logout() {
    $_SESSION = [];
    session_destroy();
    header('Location: ../frontend/login.php');
    exit();
}
?>
