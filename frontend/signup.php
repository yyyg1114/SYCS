<?php
require_once __DIR__ . '/../backend/db.php';

$msg = '';
$err = '';
$success = false;

if (isset($_GET['email'], $_GET['username'], $_GET['password'])) {
    $e = $_GET['email'];
    $u = $_GET['username'];
    $p = $_GET['password'];

    $check = $mysqli->query("SELECT id FROM users WHERE email='$e'");
    if ($check && $check->num_rows > 0) {
        $err = 'このメールアドレスは使用されています';
    } else {
        if ($mysqli->query(
            "INSERT INTO users (email,username,password) VALUES ('$e','$u','$p')"
        )) {
            $msg = '登録が完了しました。ログイン画面へ移動します。';
            $success = true;
        } else {
            $err = '登録に失敗しました';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Sign up | SYCS Shinjuku Yamabuki Chat System</title>
    <link rel="stylesheet" href="css/style.css">

    <?php if ($success): ?>
        <!-- 5秒後にログイン画面へ -->
        <script>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 5000);
        </script>
    <?php endif; ?>
</head>

<body>

    <main>
        <div class="card">
            <h2>Create account</h2>

            <?php if ($msg): ?>
                <div class="message success">
                    <?= htmlspecialchars($msg) ?><br>
                    <small>5秒後に自動で移動します</small>
                </div>
            <?php endif; ?>

            <?php if ($err): ?>
                <div class="message error"><?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="get">
                    <label>
                        email
                        <input type="text" name="email" placeholder="admin@example.com" required>
                    </label>
                    <label>
                        Username
                        <input type="text" name="username" placeholder="username" required>
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" placeholder="password" required>
                    </label>
                    <button type="submit">Sign up</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        © 2026 SYCS · Terms · Privacy
    </footer>

</body>

</html>
