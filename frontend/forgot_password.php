<?php
require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
SecurityUtil::sendSecurityHeaders();
require_once __DIR__ . '/../backend/Mailer.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "不正なリクエストです。";
        $messageType = "error";
    } else {
        $email = $_POST['email'] ?? '';
        if ($email) {
            $emailHash = hash('sha256', $email);
            $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE email_hash = ? LIMIT 1");
            $stmt->bind_param("s", $emailHash);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $token = SecurityUtil::generateToken();

                $upd = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $upd->bind_param("si", $token, $row['id']);
                $upd->execute();

                $dbEmail = SecurityUtil::decrypt($row['email']);
                if (Mailer::sendPasswordReset($dbEmail, $row['username'], $token)) {
                    $message = "パスワードリセットの案内をメールで送信しました。";
                    $messageType = "success";
                } else {
                    $message = "メールの送信に失敗しました。";
                    $messageType = "error";
                }
            } else {
                // セキュリティ上、メールアドレスが存在しない場合も同じメッセージを出す
                $message = "もしそのメールアドレスが登録されていれば、案内を送信しました。";
                $messageType = "success";
            }
        } else {
            $message = "メールアドレスを入力してください。";
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
    <title>Forgot Password | SYCS</title>
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
            background: radial-gradient(circle at 100% 0%, #1e1b4b 0%, #0f0f10 50%),
                radial-gradient(circle at 0% 100%, #312e81 0%, #0f0f10 50%);
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
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        h2 {
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.025em;
        }

        p.description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
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
            margin-bottom: 2rem;
            border: 1px solid;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .back-link:hover {
            text-decoration: underline;
            color: var(--accent-hover);
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>リセット</h2>
        <p class="description">登録済みのメールアドレスを入力してください。<br>パスワード再設定用のリンクをお送りします。</p>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($messageType !== 'success'): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="input-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" name="email" placeholder="example@domain.com" required autofocus>
                </div>
                <button type="submit">案内を送信する</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← ログイン画面に戻る</a>
    </div>
</body>

</html>
