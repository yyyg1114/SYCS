<?php
// Secure Session Settings (Must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    // セッションIDの再生成間隔（秒）
    ini_set('session.gc_maxlifetime', 3600); // 1時間でサーバー側GC
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,          // ブラウザ閉じたら削除
        'path' => '/',
        'domain' => '',           // カレントドメインに自動設定
        'secure' => $isHttps,     // HTTPS時のみ送信
        'httponly' => true,       // JavaScriptからアクセス不可
        'samesite' => 'Lax'       // OAuthリダイレクト(Google等)に対応するためLaxに設定
    ]);
    session_start();

    // セッションハイジャック対策: Risk-based validation
    // Note: IP address validation was too strict for mobile/VPN users; now we use soft checks.
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $uaHash    = hash('sha256', $currentUa);

    if (isset($_SESSION['_security_ua'])) {
        // 既存セッションの検証: User-Agent のみをチェック（IP は除外）
        // User-Agent が完全に異なる = ブラウザ変更、ログアウト推奨
        if ($_SESSION['_security_ua'] !== $uaHash) {
            // User-Agent が変わった: 警告ログし、セッション再生成を推奨（破棄しない）
            error_log("Session warning: User-Agent changed (session ID: " . session_id() . ")");
            // セッション再生成（セッション固定攻撃対策）
            session_regenerate_id(true);
            $_SESSION['_security_ua'] = $uaHash;
        }
    } else {
        // 新規セッション: User-Agent をセキュリティ情報として記録
        $_SESSION['_security_ua'] = $uaHash;
    }

    // セッション固定攻撃対策: 定期的にセッション ID を再生成
    // $_SESSION['_regenerate_time'] で管理し、15分ごとに再生成
    $regenerateInterval = 900; // 15分（秒）
    if (!isset($_SESSION['_regenerate_time'])) {
        $_SESSION['_regenerate_time'] = time();
        session_regenerate_id(true);
    } elseif (time() - $_SESSION['_regenerate_time'] > $regenerateInterval) {
        session_regenerate_id(true);
        $_SESSION['_regenerate_time'] = time();
    }
}
