<?php
require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
SecurityUtil::sendSecurityHeaders();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, username, password, is_verified, last_thread_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($p, $row['password'])) {
            if ($row['is_verified'] == 0) {
                $error = 'メールアドレスの本登録が完了していません。';
            } else {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user'] = $row['username'];
                $_SESSION['last_thread_id'] = $row['last_thread_id'] ?: 1;
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'ユーザー名またはパスワードが正しくありません。';
        }
    } else {
        $error = 'ユーザー名またはパスワードが正しくありません。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/x-icon">
    <title>Login | SYCS</title>
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
            background: radial-gradient(circle at 0% 0%, #1e1b4b 0%, #0f0f10 50%),
                radial-gradient(circle at 100% 100%, #312e81 0%, #0f0f10 50%);
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
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            margin-bottom: 2rem;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 2.2rem;
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

        .error-box {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .links {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .forgot-link {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: 0.2s;
        }

        .forgot-link:hover {
            color: var(--accent-color);
        }

        .signup-promo {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .signup-promo a {
            color: var(--accent-color);
            font-weight: 600;
            text-decoration: none;
        }

        footer {
            position: fixed;
            bottom: 2rem;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>SYCS</h1>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label for="username">ユーザー名</label>
                <input type="text" id="username" name="username" placeholder="Username" required autofocus>
            </div>
            <div class="input-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">ログイン</button>
        </form>

        <div class="links">
            <a href="forgot_password.php" class="forgot-link">パスワードを忘れましたか？</a>
            <p class="signup-promo">
                アカウントをお持ちでないですか？ <a href="signup.php">新規登録</a>
            </p>
        </div>
    </div>

    <footer>
        &copy; 2026 SYCS · Shinjuku Yamabuki Chat System
    </footer>
</body>

</html>
