<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_init.php';

db_init($mysqli);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    die("ユーザー名とパスワードを入力してください。");
}

// --- ログイン試行制限チェック ---
$ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$lockMinutes = 15;
$lockWindow  = $lockMinutes * 60; // 秒換算
$maxAttempts = 5;

// 失敗時の記録用: username と IP の両方を記録して、名前・IP の両方をロック対象にする
// 既存スキーマ `login_attempts(identifier, attempted_at)` を再利用する。
$countByUser = 0;
$stmtU = $mysqli->prepare(
    "SELECT COUNT(*) FROM login_attempts
    WHERE identifier = ?
    AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
);
if ($stmtU) {
    $stmtU->bind_param("si", $username, $lockWindow);
    $stmtU->execute();
    $stmtU->bind_result($countByUser);
    $stmtU->fetch();
    $stmtU->close();
} else {
    error_log("login_attempts COUNT prepare failed (user): " . $mysqli->error);
}

$countByIp = 0;
$stmtI = $mysqli->prepare(
    "SELECT COUNT(*) FROM login_attempts
    WHERE identifier = ?
    AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
);
if ($stmtI) {
    $stmtI->bind_param("si", $ip, $lockWindow);
    $stmtI->execute();
    $stmtI->bind_result($countByIp);
    $stmtI->fetch();
    $stmtI->close();
} else {
    error_log("login_attempts COUNT prepare failed (ip): " . $mysqli->error);
}

// どちらか一方でも上限に達したらロック
if ((int)$countByUser >= $maxAttempts || (int)$countByIp >= $maxAttempts) {
    error_log("Login rate limited for username=[$username] or IP=[$ip]");
    die("ログイン試行回数の上限に達しました。{$lockMinutes}分後に再試行してください。");
}

// --- 認証処理 ---
$stmt = $mysqli->prepare("SELECT id, username, password, is_verified, last_thread_id FROM users WHERE username = ?");
if ($stmt === false) {
    error_log("SELECT user prepare failed: " . $mysqli->error);
    die("ログイン処理中にエラーが発生しました。しばらくしてから再試行してください。");
}

$stmt->bind_param("s", $username);
if (!$stmt->execute()) {
    error_log("SELECT user execute failed: " . $stmt->error);
    $stmt->close();
    die("ログイン処理中にエラーが発生しました。しばらくしてから再試行してください。");
}

$stmt->bind_result($userId, $dbUsername, $dbPassword, $isVerified, $lastThreadId);
$stmt->fetch();
$stmt->close();

if ($userId && $dbPassword !== null && password_verify($password, $dbPassword)) {
    if ($isVerified == 0) {
        die("メールアドレスの本登録が完了していません。メールを確認してください。");
    }

    // ログイン成功: セッションIDを再生成してセッション固定攻撃を防ぐ
    session_regenerate_id(true);

    $session = new Session();
    $session->set('user_id', $userId);
    $session->set('user', $dbUsername);
    $session->set('last_thread_id', $lastThreadId ?: 1);

    // ログイン成功時: 該当ユーザー+IPの失敗記録をクリア
    $delStmt = $mysqli->prepare(
        "DELETE FROM login_attempts WHERE identifier = ? OR identifier = ?"
    );
    if ($delStmt) {
        $delStmt->bind_param("ss", $username, $ip);
        $delStmt->execute();
        $delStmt->close();
    }

    header("Location: ../frontend/index.php");
    exit();
} else {
    // ログイン失敗: 失敗を記録（ユーザー名と IP の両方）
    error_log("Login failed for user: $username from IP: $ip");

    $insStmt = $mysqli->prepare("INSERT INTO login_attempts (identifier) VALUES (?)");
    if ($insStmt === false) {
        error_log("login_attempts INSERT prepare failed (username): " . $mysqli->error);
    } else {
        $insStmt->bind_param("s", $username);
        if (!$insStmt->execute()) {
            error_log("login_attempts INSERT execute failed (username): " . $insStmt->error);
        }
        $insStmt->close();
    }

    $insStmt2 = $mysqli->prepare("INSERT INTO login_attempts (identifier) VALUES (?)");
    if ($insStmt2 === false) {
        error_log("login_attempts INSERT prepare failed (ip): " . $mysqli->error);
    } else {
        $insStmt2->bind_param("s", $ip);
        if (!$insStmt2->execute()) {
            error_log("login_attempts INSERT execute failed (ip): " . $insStmt2->error);
        }
        $insStmt2->close();
    }

    // 認証失敗時の情報公開を避ける: どのアカウントが存在するかは漏らさない
    die("ログインに失敗しました。しばらくしてから再試行してください。");
}
