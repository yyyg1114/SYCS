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
    // セッション変数をすべてクリア
    $_SESSION = [];

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

/**
 * パスワードをハッシュ化する
 * @param string $password
 * @return string
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * パスワードを検証する
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * ハッシュの再計算が必要かチェックする
 * @param string $hash
 * @return bool
 */
function needsRehash($hash)
{
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}
