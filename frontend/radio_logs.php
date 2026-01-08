<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'] ?? null;

include '../backend/db.php';

// ページング処理
$per_page = 30;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// 総数取得
$count_sql = "SELECT COUNT(*) as total FROM radio_logs";
$count_result = $mysqli->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total = $count_row['total'];
$total_pages = ceil($total / $per_page);

// ログ取得
$sql = "SELECT id, log_datetime, source, type, type_seq, message, created_at 
        FROM radio_logs 
        ORDER BY log_datetime DESC, type_seq DESC 
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$stmt->close();
$mysqli->close();

function getTypeLabel($type) {
    $typeMap = [
        'INFO' => '通常情報',
        'MOVE' => '移動報告',
        'CHECK' => '確認事項',
        'WARN' => '警告',
        'CMD' => '指示命令',
        'COMMIT' => '完了報告'
    ];
    return $typeMap[$type] ?? $type;
}

function getTypeColor($type) {
    $colorMap = [
        'INFO' => '#007bff',
        'MOVE' => '#17a2b8',
        'CHECK' => '#ffc107',
        'WARN' => '#dc3545',
        'CMD' => '#6f42c1',
        'COMMIT' => '#28a745'
    ];
    return $colorMap[$type] ?? '#6c757d';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>無線通信ログ一覧 - Tactical-Ops-Dashboard</title>
<link rel="stylesheet" href="css/style-index.css">
<style>
    .logs-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 20px;
    }

    .logs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .logs-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .logs-table thead {
        background-color: #f5f5f5;
        border-bottom: 2px solid #ddd;
    }

    .logs-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
    }

    .logs-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }

    .logs-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .log-datetime {
        font-weight: 600;
        color: #555;
    }

    .log-source {
        background-color: #e9ecef;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 13px;
        font-weight: 600;
    }

    .log-type {
        color: white;
        padding: 4px 12px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
        text-align: center;
        min-width: 70px;
    }

    .log-seq {
        color: #999;
        font-weight: bold;
        text-align: center;
    }

    .log-message {
        color: #333;
        max-width: 400px;
        word-break: break-word;
    }

    .log-action {
        text-align: center;
    }

    .delete-btn {
        background-color: #dc3545;
        color: white;
        padding: 6px 12px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        transition: background-color 0.3s;
    }

    .delete-btn:hover {
        background-color: #c82333;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 30px;
        padding-bottom: 30px;
    }

    .pagination a, .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
    }

    .pagination a:hover {
        background-color: #007bff;
        color: white;
    }

    .pagination span {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
        border-color: #ccc;
    }

    .empty-message {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-message p {
        margin-bottom: 30px;
        font-size: 16px;
    }

    .btn-add {
        background-color: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
    }

    .btn-add:hover {
        background-color: #218838;
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

<div class="logs-container">
    <div class="logs-header">
        <h2>無線通信ログ一覧</h2>
        <a href="radio_logs_add.php" class="btn-add">+ 新規追加</a>
    </div>

    <?php if (count($logs) > 0): ?>
        <table class="logs-table">
            <thead>
                <tr>
                    <th style="width: 150px;">通信日時</th>
                    <th style="width: 180px;">送信元</th>
                    <th style="width: 120px;">種別</th>
                    <th style="width: 40px;">番号</th>
                    <th>メッセージ</th>
                    <th style="width: 80px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="log-datetime">
                            <?= htmlspecialchars(date('m/d H:i:s', strtotime($log['log_datetime']))) ?>
                        </td>
                        <td>
                            <span class="log-source"><?= htmlspecialchars($log['source']) ?></span>
                        </td>
                        <td>
                            <span class="log-type" style="background-color: <?= getTypeColor($log['type']) ?>;">
                                <?= getTypeLabel($log['type']) ?>
                            </span>
                        </td>
                        <td class="log-seq"><?= htmlspecialchars($log['type_seq']) ?></td>
                        <td class="log-message"><?= htmlspecialchars($log['message']) ?></td>
                        <td class="log-action">
                            <form method="POST" style="display: inline;" onsubmit="return deleteLogWithRedirect(event, <?= $log['id'] ?>)">
                                <button type="submit" class="delete-btn">削除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ページング -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">« 最初</a>
                    <a href="?page=<?= $page - 1 ?>">‹ 前へ</a>
                <?php else: ?>
                    <span class="disabled">« 最初</span>
                    <span class="disabled">‹ 前へ</span>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) {
                    echo '<span class="disabled">...</span>';
                }

                for ($i = $start; $i <= $end; $i++) {
                    if ($i == $page) {
                        echo "<span>$i</span>";
                    } else {
                        echo "<a href=\"?page=$i\">$i</a>";
                    }
                }

                if ($end < $total_pages) {
                    echo '<span class="disabled">...</span>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">次へ ›</a>
                    <a href="?page=<?= $total_pages ?>">最後 »</a>
                <?php else: ?>
                    <span class="disabled">次へ ›</span>
                    <span class="disabled">最後 »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px; color: #999;">
            全 <?= htmlspecialchars($total) ?> 件中、<?= htmlspecialchars(($offset + 1) . '～' . min($offset + $per_page, $total)) ?> 件を表示
        </div>

    <?php else: ?>
        <div class="empty-message">
            <p>まだラジオログがありません。</p>
            <a href="radio_logs_add.php" class="btn-add">最初のログを追加する</a>
        </div>
    <?php endif; ?>

</div>

<footer>
© 2025 Tactical-Ops-Dashboard · Terms · Privacy
</footer>

<script>
async function deleteLogWithRedirect(event, logId) {
    event.preventDefault();
    
    if (!confirm('このログを削除しますか？')) {
        return false;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', logId);
        
        const response = await fetch('../backend/radio_logs.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // radio_logs.phpに戻る
            window.location.href = 'radio_logs.php';
        } else {
            alert('削除失敗: ' + (data.error || '不明なエラー'));
            return false;
        }
    } catch (error) {
        console.error('削除エラー:', error);
        alert('削除処理中にエラーが発生しました');
        return false;
    }
}
</script>

</body>
</html>
