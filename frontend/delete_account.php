<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Updated redirect to index.php (login)
    exit();
}
$user = $_SESSION['user'] ?? null;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        include '../backend/db.php';
        $user_id = $_SESSION['user_id']; // Use session ID directly for safety

        if ($user_id) {
            $mysqli->begin_transaction();
            try {
                // Keep minimal logic: just delete user. 
                // Assumes CASCADING deletes in DB schema for related data (messages, friends, etc),
                // otherwise we should manually delete them.
                // Given the init.sql seen earlier, cascading handles most things?
                // init.sql had ON DELETE CASCADE for foreign keys.
                
                $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    $mysqli->commit();
                    $mysqli->close();
                    session_destroy();
                    header('Location: index.php?deleted=1');
                    exit();
                } else {
                    throw new Exception('ユーザーの削除に失敗しました');
                }
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = 'エラーが発生しました: ' . $e->getMessage();
            }
        }
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account | SYCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f0f10;
            --accent-color: #ef4444; /* Red for Danger */
            --accent-hover: #dc2626;
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
            max-width: 480px;
            text-align: center;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.8rem;
        }

        .alert-warning {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: left;
            line-height: 1.6;
        }

        .checkbox-group {
            text-align: left;
            margin: 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.9rem;
            color: var(--text-secondary);
            transition: color 0.2s;
        }

        .checkbox-group label:hover {
            color: var(--text-primary);
        }

        .checkbox-group input {
            appearance: none;
            width: 1.25em;
            height: 1.25em;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            margin-right: 0.75rem;
            display: grid;
            place-content: center;
            transition: 0.2s;
            cursor: pointer;
            background: var(--input-bg);
        }

        .checkbox-group input::before {
            content: "";
            width: 0.65em;
            height: 0.65em;
            transform: scale(0);
            transition: 0.12s transform ease-in-out;
            box-shadow: inset 1em 1em white;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }

        .checkbox-group input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .checkbox-group input:checked::before {
            transform: scale(1);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        button, .btn-cancel {
            flex: 1;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
            border: none;
            display: inline-block;
        }

        .btn-danger {
            background-color: var(--accent-color);
            color: white;
            opacity: 0.5;
            pointer-events: none;
        }

        .btn-danger.active {
            opacity: 1;
            pointer-events: auto;
        }

        .btn-danger.active:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-cancel {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .btn-cancel:hover {
            background-color: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>アカウント削除</h2>
        
        <?php if ($error): ?>
            <div style="color: #ef4444; margin-bottom: 1rem; text-align: left; padding: 10px; background: rgba(255,0,0,0.1); border-radius: 8px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="alert-warning">
            <strong>⚠ 警告:</strong><br>
            この操作は取り消せません。<br>
            メッセージ履歴、フレンドリスト、アップロードしたファイルを含む全てのデータが永久に削除されます。
        </div>

        <form method="POST">
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" id="confirm1" name="confirm1" value="yes">
                    すべてのデータが削除されることを理解しました
                </label>
                <label>
                    <input type="checkbox" id="confirm2" name="confirm2" value="yes">
                    この操作は復元できないことを理解しました
                </label>
                <label>
                    <input type="checkbox" id="confirm3" name="confirm3" value="yes">
                    アカウント削除を実行します
                </label>
            </div>

            <div class="btn-group">
                <a href="index.php" class="btn-cancel">キャンセル</a>
                <button type="submit" name="confirm_delete" value="yes" class="btn-danger" id="deleteBtn">
                    削除を実行
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = [
                document.getElementById('confirm1'),
                document.getElementById('confirm2'),
                document.getElementById('confirm3')
            ];
            const deleteBtn = document.getElementById('deleteBtn');

            function updateButton() {
                const allChecked = checkboxes.every(cb => cb.checked);
                if (allChecked) {
                    deleteBtn.classList.add('active');
                } else {
                    deleteBtn.classList.remove('active');
                }
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateButton));
        });
    </script>
</body>
</html>
