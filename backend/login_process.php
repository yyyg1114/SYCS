<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_init.php';
require_once __DIR__ . '/SecurityUtil.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/LoginSecurity.php';

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
// 既存スキーマ `login_attempts(identifier, attempted_at)` を再利用しつつ、code_verify と password_failure を分離する。
$countByUser = 0;
$stmtU = $mysqli->prepare(
    "SELECT COUNT(*) FROM login_attempts
    WHERE purpose = 'password_failure'
    AND identifier = ?
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
    WHERE purpose = 'password_failure'
    AND identifier = ?
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
$stmt = $mysqli->prepare("SELECT id, username, password, is_verified, last_thread_id, email FROM users WHERE username = ?");
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

$stmt->bind_result($userId, $dbUsername, $dbPassword, $isVerified, $lastThreadId, $encryptedEmail);
$stmt->fetch();
$stmt->close();

if ($userId && $dbPassword !== null && password_verify($password, $dbPassword)) {
    if ($isVerified == 0) {
        die("メールアドレスの本登録が完了していません。メールを確認してください。");
    }

    $email = $encryptedEmail ? SecurityUtil::decrypt($encryptedEmail) : null;
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("登録メールアドレスを確認できないため、ログインできません。");
    }

    $existingPending = $_SESSION['pending_login'] ?? [];
    $rateLimit = isLoginCodeIssueRateLimited($mysqli, (int)$userId, $ip, 3, 900);
    if (!$rateLimit['allowed']) {
        error_log(sprintf('Login code issuance rate limited for user_id=%d ip=%s user_count=%d ip_count=%d', (int)$userId, $ip, $rateLimit['user_count'], $rateLimit['ip_count']));
        die("確認コードの送信回数が上限に達しました。15分ほど経ってから再試行してください。");
    }

    $code = (string)random_int(100000, 999999);
    if (!Mailer::sendLoginCode($email, $dbUsername, $code)) {
        die("確認コードを送信できませんでした。しばらくしてから再試行してください。");
    }
    recordLoginAttempt($mysqli, 'code_issue', (int)$userId, $ip, $dbUsername);
    $_SESSION['pending_login'] = buildPendingLoginState($existingPending, [
        'user_id' => (int)$userId,
        'username' => $dbUsername,
        'last_thread_id' => (int)$lastThreadId,
        'code_hash' => hash('sha256', $code),
        'expires' => time() + 600,
        'attempts' => 0,
    ]);

    header("Location: ../frontend/login.php");
    exit();
} else {
    // ログイン失敗: 失敗を記録（ユーザー名と IP の両方）
    error_log("Login failed for user: $username from IP: $ip");
    recordLoginAttempt($mysqli, 'password_failure', null, $ip, $username);
    recordLoginAttempt($mysqli, 'password_failure', null, $ip, $ip);

    // 認証失敗時の情報公開を避ける: どのアカウントが存在するかは漏らさない
    die("ログインに失敗しました。しばらくしてから再試行してください。");
}
