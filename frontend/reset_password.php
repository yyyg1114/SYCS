<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$message = '';
$messageType = '';
$success = false;

if (!$token) {
    die("不正なアクセスです。トークンがありません。");
}

// Check token and expiry
$stmt = $mysqli->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    $message = "無効なトークンか、有効期限が切れています。再度リクエストしてください。";
    $messageType = "error";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['confirm_password'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "パスワードが一致しません。";
        $messageType = "error";
    } else if (strlen($password) < 8) {
        $message = "パスワードは8文字以上で入力してください。";
        $messageType = "error";
    } else {
        $newPass = password_hash($password, PASSWORD_DEFAULT);
        $upd = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $upd->bind_param("si", $newPass, $user['id']);
        
        if ($upd->execute()) {
            $message = "パスワードを更新しました。3秒後にログイン画面へ移動します。";
            $messageType = "success";
            $success = true;
        } else {
            $message = "エラーが発生しました。時間を置いて再度お試しください。";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/x-icon">
    <title>Reset Password | SYCS</title>
    <?php if ($success): ?>
        <meta http-equiv="refresh" content="3;url=login.php">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f0f10;
            --accent-color: #6366f1;
            --accent-hover: #818cf8;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --card-bg: rgba(30, 31, 35, 0.7);
            --input-bg: rgba(255, 255, 255, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            background: radial-gradient(circle at 0% 100%, #1e1b4b 0%, #0f0f10 50%),
                        radial-gradient(circle at 100% 0%, #312e81 0%, #0f0f10 50%);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 3.5rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            width: 100%;
            max-width: 420px;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            margin-bottom: 2rem;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.025em;
        }

        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            margin-left: 0.5rem;
        }

        input {
            width: 100%;
            padding: 1rem;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 1rem;
        }

        button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .message {
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .message.success {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.2);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .back-to-login {
            display: inline-block;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: 0.2s;
        }

        .back-to-login:hover {
            color: var(--accent-color);
        }
        
        .timer-dots {
            display: inline-block;
            width: 10px;
            text-align: left;
            animation: dots 1.5s infinite;
        }
        
        @keyframes dots {
            0% { content: ''; }
            33% { content: '.'; }
            66% { content: '..'; }
            100% { content: '...'; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>パスワード再設定</h2>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
                <?php if ($success): ?>
                    <span id="dots">...</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success && $user): ?>
            <form method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="input-group">
                    <label for="password">新しいパスワード (8文字以上)</label>
                    <input type="password" id="password" name="password" required minlength="8" autofocus>
                </div>
                <div class="input-group">
                    <label for="confirm_password">パスワード（確認）</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit">パスワードを更新する</button>
            </form>
        <?php endif; ?>

        <?php if (!$user && $messageType === 'error'): ?>
            <a href="forgot_password.php" class="back-to-login">リセットリンクを再送する</a>
        <?php else: ?>
            <a href="login.php" class="back-to-login">ログイン画面に戻る</a>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($success): ?>
        // Simple dots animation
        let dots = 0;
        setInterval(() => {
            dots = (dots + 1) % 4;
            document.getElementById('dots').innerText = '.'.repeat(dots);
        }, 500);
        <?php endif; ?>

        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                const p = document.getElementById('password').value;
                const c = document.getElementById('confirm_password').value;
                if (p !== c) {
                    e.preventDefault();
                    alert('パスワードが一致しません。');
                }
            });
        }
    </script>
</body>
</html>
