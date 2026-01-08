<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'] ?? null;

// POST送信時の処理
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../backend/db.php';
    
    // datetime-local形式の値を DATETIME形式に変換
    $log_datetime_input = $_POST['log_datetime'] ?? '';
    if ($log_datetime_input) {
        $log_datetime = str_replace('T', ' ', $log_datetime_input) . ':00';
    } else {
        $log_datetime = date('Y-m-d H:i:s');
    }
    
    $source = $_POST['source'] ?? '';
    $type = $_POST['type'] ?? 'INFO';
    $message_text = $_POST['message'] ?? '';
    
    // バリデーション
    if (empty($source)) {
        $error = '送信元は必須です';
    } elseif (empty($message_text)) {
        $error = 'メッセージは必須です';
    } else {
        // type_seqを自動生成
        $seq_sql = "SELECT MAX(type_seq) as max_seq FROM radio_logs WHERE type = ?";
        $seq_stmt = $mysqli->prepare($seq_sql);
        $seq_stmt->bind_param('s', $type);
        $seq_stmt->execute();
        $seq_result = $seq_stmt->get_result();
        $seq_row = $seq_result->fetch_assoc();
        $type_seq = ($seq_row['max_seq'] ?? 0) + 1;
        $seq_stmt->close();
        
        // データ挿入
        $sql = "INSERT INTO radio_logs (log_datetime, source, type, type_seq, message) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssss', $log_datetime, $source, $type, $type_seq, $message_text);
        
        if ($stmt->execute()) {
            $message = 'ラジオログを追加しました';
        } else {
            $error = 'データベースエラーが発生しました';
        }
        
        $stmt->close();
        $mysqli->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>無線通信ログ追加 - Tactical-Ops-Dashboard</title>
<link rel="stylesheet" href="css/style-index.css">
<style>
    .add-form {
        max-width: 600px;
        margin: 20px auto;
        padding: 30px;
        background-color: #f5f5f5;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        font-family: inherit;
        box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0,123,255,0.3);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }
    
    .button-group {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #0056b3;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #545b62;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .button-group {
            flex-direction: column;
        }
    }
</style>
</head>
<body>

<header>
<div class="header-inner" style="display:flex; align-items:center; justify-content:space-between;">
    <h1>Tactical-Ops-Dashboard</h1>
    <nav>
    <?php if ($user): ?>
        <span>ようこそ <?= htmlspecialchars($user) ?> さん</span>
        <a href="index.php">Dashboard</a>
        <a href="top.php">Top</a>
        <a href="delete_account.php" style="color: #dc3545;">Account</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php" class="no-style-link">Login</a>
        <a href="signup.php" class="no-style-link">Sign up</a>
    <?php endif; ?>
    </nav>
</div>
</header>

<main>
<div class="add-form">
    <h2>無線通信ログを追加</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="log_datetime">通信日時</label>
            <input type="datetime-local" id="log_datetime" name="log_datetime" 
            value="<?= date('Y-m-d\TH:i:s') ?>" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="source">送信元</label>
                <input type="text" id="source" name="source" placeholder="例: Unit-A, Command Center" required>
            </div>
            
            <div class="form-group">
                <label for="type">種別</label>
                <select id="type" name="type">
                    <option value="INFO">通常情報 (INFO)</option>
                    <option value="MOVE">移動報告 (MOVE)</option>
                    <option value="CHECK">確認事項 (CHECK)</option>
                    <option value="WARN">警告 (WARN)</option>
                    <option value="CMD">指示命令 (CMD)</option>
                    <option value="COMMIT">完了報告 (COMMIT)</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="message">メッセージ</label>
            <textarea id="message" name="message" placeholder="通信内容を入力してください..." required></textarea>
        </div>
        
        <div class="button-group">
            <button type="submit" class="btn btn-primary">追加</button>
            <a href="radio_logs.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">キャンセル</a>
            <a href="index.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Topへ</a>
        </div>
    </form>
</div>
</main>

<footer>
© 2025 Tactical-Ops-Dashboard · Terms · Privacy
</footer>

</body>
</html>
