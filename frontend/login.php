<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

$error = '';

if (isset($_POST['username'], $_POST['password'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$u' AND password='$p' LIMIT 1";
    $res = $mysqli->query($sql);

    if ($res && $res->num_rows === 1) {
        $_SESSION['user'] = $u;
        header('Location: index.php');
        exit;
    } else {
        $error = 'ログインに失敗しました';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/x-icon">
    <title>Login | SYCS Shinjuku Yamabuki Chat System</title>
    <link rel="stylesheet" href="css/style-login.css">
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

        button {
            width: 100%;
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
</head>

<body>

    <main>
        <div class="card">
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>
                    Username
                    <input type="text" name="username" placeholder="username" aria-required="true">
                </label>
                <label>
                    Password
                    <input type="password" name="password" placeholder="password" aria-required="true">
                </label>
                <button class="button" type="submit">Log in</button>
                <a class="button" href="signup.php" class="btn">Sign up</a>
                <a class="button" href="top.php" class="btn">Top</a>
            </form>
        </div>
    </main>

    <footer>
        © 2026 SYCS · Terms · Privacy
    </footer>

</body>

</html>
