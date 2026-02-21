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

// OAuth2 Handlers
if (isset($_GET['api'])) {
    $action = $_GET['api'];

    // Discord Login
    if ($action === 'discord_login') {
        require_once __DIR__ . '/../backend/DiscordAPI.php';
        $_SESSION['oauth2_state'] = bin2hex(random_bytes(16));
        $url = DiscordAPI::getAuthorizeUrl($_SESSION['oauth2_state']);
        header("Location: $url");
        exit;
    }

    if ($action === 'discord_callback') {
        require_once __DIR__ . '/../backend/DiscordAPI.php';
        $state = $_GET['state'] ?? '';
        $code = $_GET['code'] ?? '';

        if (!$state || $state !== ($_SESSION['oauth2_state'] ?? '')) {
            header('Location: login.php?error=state_mismatch');
            exit;
        }

        $tokenData = DiscordAPI::exchangeCode($code);
        if (isset($tokenData['access_token'])) {
            $discordUser = DiscordAPI::getUserInfo($tokenData['access_token']);
            if (isset($discordUser['id'])) {
                $discordId = $discordUser['id'];
                $discordUsername = $discordUser['username'];
                $discordEmail = $discordUser['email'] ?? null;

                $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE discord_id = ?");
                $stmt->bind_param("s", $discordId);
                $stmt->execute();
                $userMatch = $stmt->get_result()->fetch_assoc();

                if (!$userMatch && $discordEmail) {
                    $emailHash = hash('sha256', $discordEmail);
                    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE email_hash = ?");
                    $stmt->bind_param("s", $emailHash);
                    $stmt->execute();
                    $userMatch = $stmt->get_result()->fetch_assoc();

                    if ($userMatch) {
                        $link = $mysqli->prepare("UPDATE users SET discord_id = ? WHERE id = ?");
                        $link->bind_param("si", $discordId, $userMatch['id']);
                        $link->execute();
                    }
                }

                if (!$userMatch) {
                    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $emailEnc = $discordEmail ? SecurityUtil::encrypt($discordEmail) : null;
                    $emailHash = $discordEmail ? hash('sha256', $discordEmail) : null;

                    $finalUsername = $discordUsername;
                    $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
                    $check->bind_param("s", $finalUsername);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        $finalUsername = $discordUsername . '_' . bin2hex(random_bytes(2));
                    }

                    $stmt = $mysqli->prepare("INSERT INTO users (username, password, email, email_hash, discord_id, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->bind_param("sssss", $finalUsername, $randomPass, $emailEnc, $emailHash, $discordId);
                    $stmt->execute();
                    $newId = $stmt->insert_id;
                    $userMatch = ['id' => $newId, 'username' => $finalUsername];
                }

                $_SESSION['user_id'] = $userMatch['id'];
                $_SESSION['user'] = $userMatch['username'];
                $_SESSION['last_thread_id'] = $userMatch['last_thread_id'] ?? 1;
                header('Location: index.php');
                exit;
            }
        }
        header('Location: login.php?error=discord_auth_failed');
        exit;
    }

    // Google Login
    if ($action === 'google_login') {
        require_once __DIR__ . '/../backend/GoogleAPI.php';
        $_SESSION['google_oauth2_state'] = bin2hex(random_bytes(16));
        $url = GoogleAPI::getAuthorizeUrl($_SESSION['google_oauth2_state']);
        header("Location: $url");
        exit;
    }

    if ($action === 'google_callback') {
        require_once __DIR__ . '/../backend/GoogleAPI.php';
        $state = $_GET['state'] ?? '';
        $code = $_GET['code'] ?? '';

        if (!$state || $state !== ($_SESSION['google_oauth2_state'] ?? '')) {
            header('Location: login.php?error=state_mismatch');
            exit;
        }

        $tokenData = GoogleAPI::exchangeCode($code);
        if (isset($tokenData['access_token'])) {
            $googleUser = GoogleAPI::getUserInfo($tokenData['access_token']);
            if (isset($googleUser['sub'])) {
                $googleId = $googleUser['sub'];
                $googleName = $googleUser['name'];
                $googleEmail = $googleUser['email'] ?? null;

                $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE google_id = ?");
                $stmt->bind_param("s", $googleId);
                $stmt->execute();
                $userMatch = $stmt->get_result()->fetch_assoc();

                if (!$userMatch && $googleEmail) {
                    $emailHash = hash('sha256', $googleEmail);
                    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE email_hash = ?");
                    $stmt->bind_param("s", $emailHash);
                    $stmt->execute();
                    $userMatch = $stmt->get_result()->fetch_assoc();

                    if ($userMatch) {
                        $link = $mysqli->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                        $link->bind_param("si", $googleId, $userMatch['id']);
                        $link->execute();
                    }
                }

                if (!$userMatch) {
                    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $emailEnc = $googleEmail ? SecurityUtil::encrypt($googleEmail) : null;
                    $emailHash = $googleEmail ? hash('sha256', $googleEmail) : null;

                    $finalUsername = $googleName;
                    $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
                    $check->bind_param("s", $finalUsername);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        $finalUsername = $googleName . '_' . bin2hex(random_bytes(2));
                    }

                    $stmt = $mysqli->prepare("INSERT INTO users (username, password, email, email_hash, google_id, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->bind_param("sssss", $finalUsername, $randomPass, $emailEnc, $emailHash, $googleId);
                    $stmt->execute();
                    $newId = $stmt->insert_id;
                    $userMatch = ['id' => $newId, 'username' => $finalUsername];
                }

                $_SESSION['user_id'] = $userMatch['id'];
                $_SESSION['user'] = $userMatch['username'];
                $_SESSION['last_thread_id'] = $userMatch['last_thread_id'] ?? 1;
                header('Location: index.php');
                exit;
            }
        }
        header('Location: login.php?error=google_auth_failed');
        exit;
    }

    // Apple Login
    if ($action === 'apple_login') {
        require_once __DIR__ . '/../backend/AppleAPI.php';
        $_SESSION['apple_oauth2_state'] = bin2hex(random_bytes(16));
        $url = AppleAPI::getAuthorizeUrl($_SESSION['apple_oauth2_state']);
        header("Location: $url");
        exit;
    }

    if ($action === 'apple_callback') {
        require_once __DIR__ . '/../backend/AppleAPI.php';
        header('Location: login.php?error=apple_auth_not_configured');
        exit;
    }

    // Outlook Login
    if ($action === 'outlook_login') {
        require_once __DIR__ . '/../backend/OutlookAPI.php';
        $_SESSION['outlook_oauth2_state'] = bin2hex(random_bytes(16));
        $url = OutlookAPI::getAuthorizeUrl($_SESSION['outlook_oauth2_state']);
        header("Location: $url");
        exit;
    }

    if ($action === 'outlook_callback') {
        require_once __DIR__ . '/../backend/OutlookAPI.php';
        $state = $_GET['state'] ?? '';
        $code = $_GET['code'] ?? '';

        if (!$state || $state !== ($_SESSION['outlook_oauth2_state'] ?? '')) {
            header('Location: login.php?error=state_mismatch');
            exit;
        }

        $tokenData = OutlookAPI::exchangeCode($code);
        if (isset($tokenData['access_token'])) {
            $outlookUser = OutlookAPI::getUserInfo($tokenData['access_token']);
            if (isset($outlookUser['id'])) {
                $outlookId = $outlookUser['id'];
                $outlookName = $outlookUser['displayName'];
                $outlookEmail = $outlookUser['mail'] ?? $outlookUser['userPrincipalName'] ?? null;

                $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE outlook_id = ?");
                $stmt->bind_param("s", $outlookId);
                $stmt->execute();
                $userMatch = $stmt->get_result()->fetch_assoc();

                if (!$userMatch && $outlookEmail) {
                    $emailHash = hash('sha256', $outlookEmail);
                    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE email_hash = ?");
                    $stmt->bind_param("s", $emailHash);
                    $stmt->execute();
                    $userMatch = $stmt->get_result()->fetch_assoc();

                    if ($userMatch) {
                        $link = $mysqli->prepare("UPDATE users SET outlook_id = ? WHERE id = ?");
                        $link->bind_param("si", $outlookId, $userMatch['id']);
                        $link->execute();
                    }
                }

                if (!$userMatch) {
                    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $emailEnc = $outlookEmail ? SecurityUtil::encrypt($outlookEmail) : null;
                    $emailHash = $outlookEmail ? hash('sha256', $outlookEmail) : null;

                    $finalUsername = $outlookName;
                    $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
                    $check->bind_param("s", $finalUsername);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        $finalUsername = $outlookName . '_' . bin2hex(random_bytes(2));
                    }

                    $stmt = $mysqli->prepare("INSERT INTO users (username, password, email, email_hash, outlook_id, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->bind_param("sssss", $finalUsername, $randomPass, $emailEnc, $emailHash, $outlookId);
                    $stmt->execute();
                    $newId = $stmt->insert_id;
                    $userMatch = ['id' => $newId, 'username' => $finalUsername];
                }

                $_SESSION['user_id'] = $userMatch['id'];
                $_SESSION['user'] = $userMatch['username'];
                $_SESSION['last_thread_id'] = $userMatch['last_thread_id'] ?? 1;
                header('Location: index.php');
                exit;
            }
        }
        header('Location: login.php?error=outlook_auth_failed');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/SYCS_favicon.svg">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SYCS">
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

        .btn-discord {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            background-color: #5865f2;
            color: #ffffff;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            box-sizing: border-box;
            font-size: 0.85rem;
        }

        .btn-discord:hover {
            background-color: #4752c4;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(88, 101, 242, 0.4);
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            background-color: #ffffff;
            color: #3c4043;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid #dadce0;
            cursor: pointer;
            box-sizing: border-box;
            font-size: 0.85rem;
        }

        .btn-google:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
        }

        .btn-apple {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            background-color: #000000;
            color: #ffffff;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            box-sizing: border-box;
            font-size: 0.85rem;
        }

        .btn-apple:hover {
            background-color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.4);
        }

        .btn-outlook {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            background-color: #0078d4;
            color: #ffffff;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            box-sizing: border-box;
            font-size: 0.85rem;
        }

        .btn-outlook:hover {
            background-color: #005a9e;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 120, 212, 0.4);
        }

        .oauth-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .oauth-buttons a.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            filter: grayscale(1);
            box-shadow: none !important;
            transform: none !important;
        }

        .divider {
            margin: 1.5rem 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }

        .divider span {
            position: relative;
            background: #1e1f22;
            padding: 0 1rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            z-index: 2;
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

        <div class="divider">
            <span>または</span>
        </div>

        <div class="oauth-buttons">
            <a href="login.php?api=google_login" class="btn-google">
                <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Googleでログイン
            </a>
            <a href="login.php?api=discord_login" class="btn-discord">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037 19.736 19.736 0 0 0-4.885 1.515.069.069 0 0 0-.032.027C.533 9.048-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.086 2.157 2.419 0 1.334-.947 2.419-2.157 2.419zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.086 2.157 2.419 0 1.334-.946 2.419-2.157 2.419z" />
                </svg>
                Discordでログイン
            </a>
            <a class="btn-apple disabled">
                <svg width="25" height="25" class="svg-icon" style="width: 1em;height: 1em;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <path d="M394.336 953.176l-2.76-0.04c-55.888-1.264-117.736-53.12-169.584-142.256-57.704-99.624-86.24-227.368-72.72-325.48 13.712-99.352 87.888-190.504 172.528-212.032 19.512-4.912 37.664-7.296 55.536-7.296 40.784 0 70.888 12.872 95.072 23.216 17.84 7.624 31.944 13.656 46.288 13.656-0.312 0 15.024-0.72 45.224-13.24 2.872-1.2 5.84-2.488 8.832-3.768 25.248-10.864 53.856-23.184 95.616-23.184 17.528 0 35.696 2.144 55.528 6.544 80.928 17.96 128.672 85.6 130.664 88.472a24 24 0 0 1-7.08 34.088c-3.896 2.448-96.664 61.888-77.048 160.76 20.024 101 94.192 127.296 97.336 128.368a24.144 24.144 0 0 1 14.92 31.16c-2.072 5.56-51.432 136.752-109.072 185.8-40.624 34.44-65.12 55.2-101.568 55.2-10.832 0-22.48-1.864-36.656-5.872-16.2-4.552-30.504-10.368-43.12-15.504-20.872-8.488-37.36-15.192-56.832-15.192-4.296 0-8.736 0.344-13.192 1.008-18.944 2.824-37.464 10.088-55.368 17.104-23.168 9.096-47.136 18.488-72.544 18.488zM377.344 314.08c-13.872 0-28.192 1.904-43.76 5.832-56.296 14.32-124.624 84.184-136.76 172.056-12.152 88.192 14.024 203.904 66.68 294.824 50.168 86.248 99.664 117.704 129.256 118.376l1.568 0.024c16.336 0 35.136-7.368 55.032-15.168 19.448-7.624 41.488-16.264 65.792-19.88a136.608 136.608 0 0 1 20.288-1.536c28.856 0 52.264 9.52 74.904 18.72 12.016 4.88 24.432 9.936 38.064 13.776 9.832 2.776 17.336 4.072 23.632 4.072 17.384 0 31.464-10.696 70.496-43.784 33.696-28.68 69.008-102.296 87.016-145.472-32.096-17.96-87.912-61.608-106.216-153.936-19.456-98.016 41.848-165.128 76.68-194.4-16.848-17.392-47.016-42.632-86.528-51.4-16.376-3.632-31.136-5.408-45.128-5.408-31.864 0-53.616 9.368-76.64 19.272-3.128 1.344-6.232 2.68-9.336 3.984-38.896 16.128-59.888 16.936-63.704 16.936-24.176 0-45.008-8.904-65.152-17.52-22.232-9.536-45.232-19.368-76.184-19.368z" fill="#e4e4e4ff" />
                    <path d="M516.544 281.464a23.992 23.992 0 0 1-23.848-21.608c-0.376-3.704-8.472-91.472 46.96-149.328 53.448-55.408 130.832-69.584 134.096-70.16a24 24 0 0 1 27.632 18.568c0.832 3.848 19.592 94.752-40.384 155.768-57.096 58.072-138.728 66.328-142.176 66.656a24.528 24.528 0 0 1-2.28 0.104z m140.312-186.928c-22.896 7.664-56.904 22.608-82.6 49.248-23.992 25.048-31.344 60.36-33.4 84.448 23.8-6.368 58.992-19.816 85.904-47.184 25.896-26.344 30.448-62.488 30.096-86.512z" fill="#e4e4e4ff" />
                </svg>
                Appleでログイン (無効化中)
            </a>
            <a class="btn-outlook disabled">
                <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                    <path fill="#0078d4" d="M1 1h10v10H1zM13 1h10v10H13zM1 13h10v10H1zM13 13h10v10H13z" />
                </svg>
                Outlookでログイン (無効化中)
            </a>
        </div>

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
