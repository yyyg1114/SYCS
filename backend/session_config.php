<?php
// Secure Session Settings (Must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    // セッションIDの再生成間隔（秒）
    ini_set('session.gc_maxlifetime', 3600); // 1時間でサーバー側GC
    session_set_cookie_params([
        'lifetime' => 0,          // ブラウザ閉じたら削除
        'path' => '/',
        'domain' => '',           // カレントドメインに自動設定
        'secure' => isset($_SERVER['HTTPS']), // HTTPS時のみ送信
        'httponly' => true,       // JavaScriptからアクセス不可
        'samesite' => 'Strict'    // CSRF対策
    ]);
    session_start();

    // セッションハイジャック対策: IPアドレスとUser-Agentの変化を検知
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipHash    = hash('sha256', $currentIp);
    $uaHash    = hash('sha256', $currentUa);

    if (isset($_SESSION['_security_ip'], $_SESSION['_security_ua'])) {
        // 既存セッションの検証
        if (
            $_SESSION['_security_ip'] !== $ipHash ||
            $_SESSION['_security_ua'] !== $uaHash
        ) {
            // 不審なセッション: 全データを破棄して新規セッションを開始
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    } else {
        // 新規セッション: セキュリティ情報を記録
        $_SESSION['_security_ip'] = $ipHash;
        $_SESSION['_security_ua'] = $uaHash;
    }
}
