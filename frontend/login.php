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
    <title>Login | SYCS</title>
    <link rel="stylesheet" href="css/style-login.css">
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
