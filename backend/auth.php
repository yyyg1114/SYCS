<?php
require_once __DIR__ . '/session_config.php';
require 'db.php';

/**
 * ユーザーがログインしているかをチェック
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * ログイン必須ページで呼ぶ
 * ログインしていなければ login.php にリダイレクト
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../frontend/login.php');
        exit();
    }
}

/**
 * 現在のログインユーザー情報を取得
 */
function getCurrentUser($mysqli)
{
    if (!isset($_SESSION['user_id'])) return null;

    $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * ログアウト処理
 */
function logout()
{
    $_SESSION = [];
    session_destroy();
    header('Location: ../frontend/login.php');
    exit();
}
