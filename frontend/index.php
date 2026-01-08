<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'] ?? null;
?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>SYCS</title>
    <link rel="stylesheet" href="css/style-index.css">
</head>

<body>

    <header>
        <div class="header-inner" style="display:flex; align-items:center; justify-content:space-between;">
            <h1>SYCS</h1>

            <nav>
                <?php if ($user): ?>
                    <span>ようこそ <?=
                                htmlspecialchars($user) ?> さん</span>
                    <a href="delete_account.php" style="color: #dc3545;">Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="no-style-link">Login</a>
                    <a href="signup.php" class="no-style-link">Sign up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section id="radio-section" class="section2">
        <h2>Chat</h2>
        <div id="">

        </div>
    </section>


    <script src="js/time.js"></script>
    <script src="js/locate.js"></script>
    <script src="js/radio.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Radio 初回ロード
            if (typeof loadRadio === "function") loadRadio();

            // GPS 位置情報取得の初期化
            locationManager.init('gps-status', 1000);

            // Radio 3秒更新
            setInterval(() => {
                if (typeof loadRadio === "function") loadRadio();
            }, 3000);
        });
    </script>
    <footer>
        © 2026 SYCS · Terms · Privacy
    </footer>
</body>

</html>
