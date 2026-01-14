<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'] ?? null;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        include '../backend/db.php';

        // セッションのユーザー情報からID取得
        // ここではusernameでユーザーを特定
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user_id = $row['id'];

            // トランザクション開始
            $mysqli->begin_transaction();

            try {
                // ユーザーアカウントを削除
                $delete_user_sql = "DELETE FROM users WHERE id = ?";
                $delete_user_stmt = $mysqli->prepare($delete_user_sql);
                $delete_user_stmt->bind_param('i', $user_id);

                if ($delete_user_stmt->execute()) {
                    $delete_user_stmt->close();

                    // コミット
                    $mysqli->commit();
                    $mysqli->close();

                    // セッション削除
                    session_destroy();

                    // ログインページへリダイレクト
                    header('Location: login.php?deleted=1');
                    exit();
                } else {
                    throw new Exception('ユーザーの削除に失敗しました');
                }
            } catch (Exception $e) {
                // ロールバック
                $mysqli->rollback();
                $error = 'エラーが発生しました: ' . $e->getMessage();
            }
        } else {
            $error = 'ユーザー情報が見つかりません';
        }

        $mysqli->close();
    } else {
        $error = '削除の確認がされていません';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>アカウント削除 - SYCS</title>
    <link rel="stylesheet" href="css/style-delete.css">
    <style>
        .delete-account-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .delete-warning {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .delete-warning h3 {
            color: #dc3545;
            margin-top: 0;
        }

        .delete-warning ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .delete-warning li {
            margin-bottom: 8px;
        }

        .checkbox-group {
            margin: 20px 0;
            padding: 15px;
            background-color: white;
            border-radius: 4px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .info-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner" style="display:flex; align-items:center; justify-content:space-between;">
            <h1>SYCS</h1>
            <nav>
                <?php if ($user): ?>
                    <span>ようこそ <?= htmlspecialchars($user) ?> さん</span>
                    <a href="index.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="no-style-link">Login</a>
                    <a href="signup.php" class="no-style-link">Sign up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <div class="delete-account-container">
            <h2>アカウント削除</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>エラー:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="info-text">
                <p>アカウント削除は取り消せません。以下の内容をよくご確認ください。</p>
            </div>

            <div class="delete-warning">
                <h3>⚠ 削除対象</h3>
                <ul>
                    <li><strong>アカウント情報</strong>: メールアドレス、ユーザー名、パスワード</li>
                    <li><strong>セッション</strong>: ログイン状態は削除後、再度ログインが必要</li>
                </ul>
                <p style="margin-top: 15px; color: #721c24;">
                    <strong>注意:</strong> この処理は取り消せません。削除されたデータは復元できません。
                </p>
            </div>

            <form method="POST">
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="confirm1" name="confirm1" value="yes">
                        <span>すべてのデータが削除されることを理解しました</span>
                    </label>
                    <label>
                        <input type="checkbox" id="confirm2" name="confirm2" value="yes">
                        <span>この操作は取り消せないことを理解しました</span>
                    </label>
                    <label>
                        <input type="checkbox" id="confirm3" name="confirm3" value="yes">
                        <span>アカウント削除を実行します</span>
                    </label>
                </div>

                <div class="button-group">
                    <a href="index.php" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger" id="deleteBtn" disabled>
                        アカウントを削除
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        © 2026 SYCS · Terms · Privacy
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const confirm1 = document.getElementById('confirm1');
            const confirm2 = document.getElementById('confirm2');
            const confirm3 = document.getElementById('confirm3');
            const deleteBtn = document.getElementById('deleteBtn');

            function updateButtonState() {
                deleteBtn.disabled = !(confirm1.checked && confirm2.checked && confirm3.checked);
            }

            confirm1.addEventListener('change', updateButtonState);
            confirm2.addEventListener('change', updateButtonState);
            confirm3.addEventListener('change', updateButtonState);

            // フォーム送信時の追加確認
            document.querySelector('form').addEventListener('submit', (e) => {
                if (!confirm('本当にアカウントを削除しますか？この操作は取り消せません。')) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>

</html>
