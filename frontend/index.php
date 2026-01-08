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
    <title>Dashboard | SYCS</title>
    <link rel="stylesheet" href="css/style-index.css">
</head>

<body>

    <header>
        <div class="header-inner" style="display:flex; align-items:center; justify-content:space-between;">
            <h1>Top</h1>

            <nav>
                <?php if ($user): ?>
                    <span>ようこそ <?=
                                htmlspecialchars($user) ?> さん</span>
                    <a href="#">Top</a>
                    <a href="delete_account.php" style="color: #dc3545;">Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="no-style-link">Login</a>
                    <a href="signup.php" class="no-style-link">Sign up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="dashboard">

        <!-- Time -->
        <section class="section1" id="clock-section">
            <h2>Time</h2>
            <iframe src="clock/analog_clock_re.html" width="1000" height="600" style="border:none;"></iframe>
        </section>

        <div class="lower-row">
            <section class="section2" id="gps-section">
                <h2>GPS</h2>
                <div id="gps-status">位置取得待機中…</div>
            </section>
        </div>


    </div>

    <script src="js/time.js"></script>
    <script src="js/locate.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Radio 初回ロード
            if (typeof loadRadio === "function") loadRadio();

            // GPS 位置情報取得の初期化
            locationManager.init('gps-status', 1000);
        });
    </script>
    <footer>
        © 2026 Top | Shinjuku Yamabuki Chat System · Terms · Privacy
    </footer>
</body>

</html>
