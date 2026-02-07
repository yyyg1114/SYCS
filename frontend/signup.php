<?php
session_start();

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
require_once __DIR__ . '/../backend/Mailer.php';

$msg = '';
$err = '';
$success = false;
$pending = isset($_GET['pending']);


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['email'], $_POST['username'], $_POST['password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $err = '不正なリクエストです (CSRF Token Mismatch)';
    } else {
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Search by username or email_hash
        $emailHash = hash('sha256', $email);
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email_hash = ? OR username = ?");
        $stmt->bind_param("ss", $emailHash, $username);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check && $check->num_rows > 0) {
            $err = 'このメールアドレスまたはユーザー名は既に使用されています';
        } else {
            $encryptedEmail = SecurityUtil::encrypt($email);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $token = SecurityUtil::generateToken();

            $stmt_insert = $mysqli->prepare("INSERT INTO users (email, email_hash, username, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt_insert->bind_param("sssss", $encryptedEmail, $emailHash, $username, $hashedPassword, $token);

            if ($stmt_insert->execute()) {
                Mailer::sendVerification($email, $username, $token);
                $msg = '仮登録が完了しました。届いたメール内のリンクをクリックして本登録を完了してください。';
                $success = true;
            } else {
                $err = '登録に失敗しました: ' . $mysqli->error;
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/x-icon">
    <title>Sign up | SYCS - Shinjuku Yamabuki Chat System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f0f10;
            --accent-color: #6366f1;
            --accent-hover: #818cf8;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #2d2e32;
            --card-bg: #1e1f23;
            --input-bg: #2a2b2f;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-color);
            background: radial-gradient(circle at top right, #1e1b4b, #0f0f10);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            background: rgba(30, 31, 35, 0.8);
            backdrop-filter: blur(12px);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 440px;
            text-align: center;
        }

        h2 {
            margin-bottom: 2rem;
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.8rem;
        }

        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: white;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        button {
            width: 109%;
            padding: 0.8rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #4ade80;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #f87171;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
            transition: 0.2s;
        }

        a:hover {
            color: var(--accent-hover);
        }
    </style>
    <?php if ($success): ?>
        <script>
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 3000);
        </script><?php endif; ?>
</head>

<body>
    <main>
        <div class="card">
            <h2>Create Account</h2>
            <?php if ($msg): ?>
                <div class="message success"><?= htmlspecialchars($msg) ?><br><small>3秒後に自動で移動します</small></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="message error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <?php if (!$success): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="form-group"><label>Email</label><input type="email" name="email" required
                            placeholder="admin@example.com"></div>
                    <div class="form-group"><label>Username</label><input type="text" name="username" required
                            placeholder="Username"></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required
                            placeholder="••••••••"></div>
                    <button type="submit">Sign up</button>
                </form>
                <div style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
                    既にアカウントをお持ちですか？ <a href="index.php">ログイン</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>
