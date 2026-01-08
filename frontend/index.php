<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'] ?? null;
?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Tactical-Ops-Dashboard</title>
<link rel="stylesheet" href="css/style-index.css">
</head>
<body>

<header>
<div class="header-inner" style="display:flex; align-items:center; justify-content:space-between;">
    <h1>Tactical-Ops-Dashboard</h1>

    <nav>
    <?php if ($user): ?>
        <span>ようこそ <?=
                    htmlspecialchars($user) ?> さん</span>
        <a href="top.php">Top</a>
        <a href="radio_logs.php">Radio Logs</a>
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

<section id="radio-section" class="section2">
<h2>Radio Logs</h2>
<ul id="radioLogs"></ul>
<div class="add-radio-btn">
    <a href="radio_logs_add.php">+ 通信内容を追加</a>
</div>
</section>
<script src="js/radio.js"></script>

</div>


</div>

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
© 2025 Tactical-Ops-Dashboard · Terms · Privacy
</footer>
</body>
</html>
