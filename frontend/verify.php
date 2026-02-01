<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

$token = $_GET['token'] ?? '';
$msg = '';
$success = false;
$username = '';

if ($token) {
    $stmt = $mysqli->prepare("SELECT id, username, last_thread_id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $upd = $mysqli->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $upd->bind_param("i", $row['id']);
        if ($upd->execute()) {
            $username = $row['username'];
            $msg = "ようこそ {$username} さん！ 本登録が完了しました。";
            $success = true;
            
            // Auto Login
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user'] = $row['username'];
            $_SESSION['last_thread_id'] = $row['last_thread_id'] ?: 1;
        } else {
            $msg = "データベース更新中にエラーが発生しました。";
        }
    } else {
        // すでに認証済みか、無効なトークン
        $msg = "このリンクは既に有効化されているか、有効期限が切れています。";
    }
} else {
    $msg = "不正なアクセスです。";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | SYCS</title>
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="3;url=index.php">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --bg: #0f0f10;
            --card-bg: #1e1f23;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .card {
            background: var(--card-bg);
            padding: 3.5rem;
            border-radius: 24px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #d1d5db;
        }
        .success-msg {
            color: var(--success);
            font-weight: 600;
        }
        .loader {
            margin: 2rem auto 0;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .redirect-text {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($success): ?>
            <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
            <h1>Verification Success!</h1>
            <p class="success-msg"><?= htmlspecialchars($msg) ?></p>
            <p>自動的にログインしました。まもなくチャット画面へ移動します...</p>
            <div class="loader"></div>
            <p class="redirect-text">切替わらない場合は <a href="index.php" style="color: var(--primary);">こちら</a> をクリックしてください。</p>
        <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
            <h1>Verification Issue</h1>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="index.php" style="color: var(--primary); text-decoration: none; margin-top: 2rem; display: inline-block;">ログイン画面へ戻る</a>
        <?php endif; ?>
    </div>
</body>
</html>
