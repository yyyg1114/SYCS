<?php
require_once __DIR__ . '/../backend/session_config.php';

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
SecurityUtil::sendSecurityHeaders();
require_once __DIR__ . '/../backend/Mailer.php';
require_once __DIR__ . '/../backend/I18n.php';

I18n::getInstance();

$msg = '';
$err = '';
$success = false;
$pending = isset($_GET['pending']);


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['email'], $_POST['username'], $_POST['password'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $err = __('invalid_request_csrf', '不正なリクエストです (CSRF Token Mismatch)');
        } else {
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Search by username or email_hash
            $emailHash = hash('sha256', $email);
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email_hash = ? OR username = ?");
            $stmt->bind_param("ss", $emailHash, $username);
            $stmt->execute();
            $check = $stmt->get_result();

            if ($check && $check->num_rows > 0) {
                $err = __('account_already_exists', 'このメールアドレスまたはユーザー名は既に使用されています');
            } else {
                $encryptedEmail = SecurityUtil::encrypt($email);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $token = SecurityUtil::generateToken();

                $stmt_insert = $mysqli->prepare("INSERT INTO users (email, email_hash, username, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt_insert->bind_param("sssss", $encryptedEmail, $emailHash, $username, $hashedPassword, $token);

                if ($stmt_insert->execute()) {
                    Mailer::sendVerification($email, $username, $token);
                    $msg = __('signup_pending_msg', '仮登録が完了しました。届いたメール内のリンクをクリックして本登録を完了してください。');
                    $success = true;
                } else {
                    $err = __('signup_failed', '登録に失敗しました') . ': ' . $mysqli->error;
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Signup error: ' . $e->getMessage());
        $err = __('unexpected_error', '予期しないエラーが発生しました。');
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/x-icon">
    <title>Sign up | SYCS - Shinjuku Yamabuki Chat System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            display: flex;
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
            view-transition-name: auth-card;
        }

        h2 {
            margin-bottom: 2rem;
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.8rem;
        }

        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: white;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        button {
            width: 109%;
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

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
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

        /* Page Transitions */
        @view-transition {
            navigation: auto;
        }

        ::view-transition-old(root) {
            animation: 0.8s cubic-bezier(0.4, 0, 0.2, 1) both fade-out;
        }

        ::view-transition-new(root) {
            animation: 0.8s cubic-bezier(0.4, 0, 0.2, 1) both fade-in;
        }

        @keyframes fade-out {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes fade-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
    <?php if ($success): ?>
        <script>
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 3000);
        </script><?php endif; ?>
</head>

<body>
    <main>
        <div class="card">
            <div style="text-align: right; margin-bottom: 1rem;">
                <select onchange="changeLang(this.value)" style="background:transparent; color:var(--text-secondary); border:none; font-size:0.85rem; cursor:pointer;">
                    <option value="ja" <?= I18n::getInstance()->getCurrentLang() === 'ja' ? 'selected' : '' ?>>日本語</option>
                    <option value="en" <?= I18n::getInstance()->getCurrentLang() === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="zh" <?= I18n::getInstance()->getCurrentLang() === 'zh' ? 'selected' : '' ?>>中文</option>
                </select>
            </div>
            <h2><?= __('signup') ?></h2>
            <?php if ($msg): ?>
                <div class="message success"><?= htmlspecialchars($msg) ?><br><small><?= __('redirect_msg', '3秒後に自動で移動します') ?></small></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="message error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <?php if (!$success): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="form-group"><label><?= __('email') ?></label><input type="email" name="email" required
                            placeholder="admin@example.com"></div>
                    <div class="form-group"><label><?= __('username') ?></label><input type="text" name="username" required
                            placeholder="Username"></div>
                    <div class="form-group"><label><?= __('password') ?></label><input type="password" name="password" required
                            placeholder="••••••••"></div>
                    <button type="submit"><?= __('signup') ?></button>
                </form>
                <div style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
                    <?= __('already_have_account') ?> <a href="index.php"><?= __('login') ?></a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        async function changeLang(lang) {
            const res = await fetch(`index.php?api=set_lang&lang=${lang}`);
            if (res.ok) {
                location.reload();
            }
        }
    </script>
</body>

</html>
