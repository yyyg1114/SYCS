<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

$error = '';

if (isset($_GET['username'], $_GET['password'])) {
$u = $_GET['username'];
$p = $_GET['password'];

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
<title>Login | Tac-Ops-Dashboard</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<main>
<div class="card">
<h2>Login</h2>

<?php if ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="get">
    <label>
    Username
    <input type="text" name="username" placeholder="username" aria-required="true">
    </label>
    <label>
    Password
    <input type="password" name="password" placeholder="password" aria-required="true">
    </label>
    <a href="index.php" class="no-style-link"><button type="submit">Log in</button></a>  
    <a href="signup.php" class="no-style-link"><button>Sign up</button></a> 
    <a href="top.php" class="no-style-link"><button>Top</button></a>
</form>
</div>
</main>

<footer>
© 2025 Tac-Ops-Dashboard · Terms · Privacy
</footer>

</body>
</html>
