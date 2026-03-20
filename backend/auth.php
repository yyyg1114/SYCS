<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/Session.php';
require 'db.php';

/**
 * ユーザーがログインしているかをチェック
 */
function isLoggedIn()
{
    return Session::has('user_id');
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
    $userId = Session::get('user_id');
    if (!$userId) return null;

    $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * ログアウト処理
 */
function logout()
{
    // Session ユーティリティを使用してセッションを破棄
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }

    // セッションクッキーを明示的に削除
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // セッションIDを再生成してから破棄（古いIDを無効化）
    session_regenerate_id(true);
    session_destroy();

    header('Location: ../frontend/login.php');
    exit();
}
