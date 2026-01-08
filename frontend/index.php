<?php session_start();
if (!isset($_SESSION['uid'])) header("Location: login.php"); ?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <h2>SYCS Web Chat</h2>

    <select id="thread"></select>
    <div id="chat"></div>

    <input id="msg">
    <button onclick="send()">送信</button>

    <hr>
    <h3>ユーザー設定</h3>
    <select id="theme">
        <option value="dark">Dark</option>
        <option value="light">Light</option>
    </select>
    <button onclick="saveSettings()">保存</button>

    <script src="js/chat.js"></script>
    <script src="js/settings.js"></script>
</body>

</html>
