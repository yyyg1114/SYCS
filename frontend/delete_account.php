<?php
require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
require_once __DIR__ . '/../backend/I18n.php';

SecurityUtil::sendSecurityHeaders();
I18n::getInstance();

// 未ログインはリダイレクト
if (empty($_SESSION['user_id']) || empty($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['user'];

// CSRF トークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF チェック
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = __('da_invalid_request', '不正なリクエストです。');
    } else {
        require_once __DIR__ . '/../backend/db.php';

        $inputPassword = $_POST['password'] ?? '';

        if (empty($inputPassword)) {
            $error = __('da_password_required', 'パスワードを入力してください。');
        } else {
            // パスワード検証
            $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row    = $result->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($inputPassword, $row['password'])) {
                $error = __('da_wrong_password', 'パスワードが正しくありません。');
            } else {
                // トランザクション開始
                $mysqli->begin_transaction();
                try {
                    $del = $mysqli->prepare('DELETE FROM users WHERE id = ?');
                    $del->bind_param('i', $userId);
                    if (!$del->execute()) {
                        throw new Exception('削除クエリの実行に失敗しました。');
                    }
                    $del->close();
                    $mysqli->commit();
                    $mysqli->close();

                    // セッションを完全破棄
                    $_SESSION = [];
                    if (ini_get('session.use_cookies')) {
                        $p = session_get_cookie_params();
                        setcookie(
                            session_name(),
                            '',
                            time() - 42000,
                            $p['path'],
                            $p['domain'],
                            $p['secure'],
                            $p['httponly']
                        );
                    }
                    session_destroy();

                    header('Location: login.php?deleted=1');
                    exit();
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $error = __('da_delete_failed', 'アカウントの削除に失敗しました。') . ' (' . htmlspecialchars($e->getMessage()) . ')';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getInstance()->getCurrentLang()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/SYCS_favicon.svg">
    <link rel="stylesheet" href="css/style-delete.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SYCS">
    <meta name="description" content="アカウントを完全に削除します。この操作は元に戻せません。">
    <title><?= __('delete_account', 'アカウント削除') ?> | SYCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="card">
        <!-- 言語セレクタ -->
        <div class="lang-selector">
            <select id="langSelect" onchange="changeLang(this.value)" aria-label="言語選択">
                <option value="ja" <?= I18n::getInstance()->getCurrentLang() === 'ja' ? 'selected' : '' ?>>日本語</option>
                <option value="en" <?= I18n::getInstance()->getCurrentLang() === 'en' ? 'selected' : '' ?>>English</option>
                <option value="zh" <?= I18n::getInstance()->getCurrentLang() === 'zh' ? 'selected' : '' ?>>中文</option>
            </select>
        </div>

        <!-- ヘッダー -->
        <div class="card-header">
            <div class="danger-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            <h1><?= __('delete_account', 'アカウント削除') ?></h1>
            <p><?= __('da_irreversible', 'この操作は取り消せません。慎重に確認してください。') ?></p>
        </div>

        <!-- エラーメッセージ -->
        <?php if ($error): ?>
            <div class="message error" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- 警告ボックス -->
        <div class="warning-box" role="note" aria-label="削除される内容">
            <ul>
                <li><span class="dot"></span><?= __('da_warn_data', 'アカウント情報（ユーザー名、メールアドレス、パスワード）が完全に削除されます。') ?></li>
                <li><span class="dot"></span><?= __('da_warn_messages', '投稿したメッセージや添付ファイルも削除対象になる場合があります。') ?></li>
                <li><span class="dot"></span><?= __('da_warn_restore', '削除されたデータは復元できません。') ?></li>
            </ul>
        </div>

        <!-- フォーム -->
        <form method="POST" id="deleteForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- パスワード確認 -->
            <div class="input-group">
                <label for="password"><?= __('da_confirm_password', 'パスワードを入力して確認') ?></label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required>
                    <button type="button" class="toggle-pw" id="togglePw" aria-label="パスワード表示切替" tabindex="-1">
                        <svg id="iconEye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="iconEyeOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 確認チェックボックス -->
            <div class="checkboxes">
                <label class="checkbox-label">
                    <input type="checkbox" id="ck1" name="ck1" value="1">
                    <span><?= __('da_ck1', 'すべてのデータが完全に削除されることを理解しました') ?></span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" id="ck2" name="ck2" value="1">
                    <span><?= __('da_ck2', 'この操作は取り消せないことを理解しました') ?></span>
                </label>
            </div>

            <!-- ボタン -->
            <div class="btn-group">
                <a href="index.php" class="btn btn-cancel"><?= __('cancel', 'キャンセル') ?></a>
                <button type="submit" id="deleteBtn" class="btn btn-delete" disabled>
                    <?= __('delete_account', 'アカウントを削除') ?>
                </button>
            </div>
        </form>
    </div>

    <footer>
        &copy; 2026 SYCS · Shinjuku Yamabuki Chat System
    </footer>

    <script>
        (() => {
            const ck1 = document.getElementById('ck1');
            const ck2 = document.getElementById('ck2');
            const pwInput = document.getElementById('password');
            const deleteBtn = document.getElementById('deleteBtn');
            const togglePw = document.getElementById('togglePw');
            const iconEye = document.getElementById('iconEye');
            const iconEyeOff = document.getElementById('iconEyeOff');

            function updateBtn() {
                const allChecked = ck1.checked && ck2.checked;
                const hasPassword = pwInput.value.trim().length > 0;
                deleteBtn.disabled = !(allChecked && hasPassword);
            }

            ck1.addEventListener('change', updateBtn);
            ck2.addEventListener('change', updateBtn);
            pwInput.addEventListener('input', updateBtn);

            // パスワード表示切替
            togglePw.addEventListener('click', () => {
                const isText = pwInput.type === 'text';
                pwInput.type = isText ? 'password' : 'text';
                iconEye.style.display = isText ? '' : 'none';
                iconEyeOff.style.display = isText ? 'none' : '';
            });

            // フォーム送信前の最終確認
            document.getElementById('deleteForm').addEventListener('submit', (e) => {
                if (!confirm('<?= addslashes(__('da_final_confirm', '本当にアカウントを削除しますか？この操作は元に戻せません。')) ?>')) {
                    e.preventDefault();
                }
            });
        })();

        async function changeLang(lang) {
            const res = await fetch(`index.php?api=set_lang&lang=${lang}`);
            if (res.ok) location.reload();
        }
    </script>
</body>

</html>
