<?php
header('Content-Type: application/json; charset=utf-8');

include 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'get') {
    // ラジオログ一覧取得
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "SELECT id, log_datetime, source, type, type_seq, message, created_at 
            FROM radio_logs 
            ORDER BY log_datetime DESC, type_seq DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    
    // 総数取得
    $count_sql = "SELECT COUNT(*) as total FROM radio_logs";
    $count_result = $mysqli->query($count_sql);
    $count_row = $count_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => $count_row['total']
    ]);

} elseif ($action === 'add') {
    // ラジオログ追加
    $log_datetime = $_POST['log_datetime'] ?? date('Y-m-d H:i:s');
    $source = $_POST['source'] ?? 'UNKNOWN';
    $type = $_POST['type'] ?? 'INFO';
    $type_seq = (int)($_POST['type_seq'] ?? 1);
    $message = $_POST['message'] ?? '';
    
    // バリデーション
    if (empty($source) || empty($message)) {
        echo json_encode([
            'success' => false,
            'error' => '送信元とメッセージは必須です'
        ]);
        exit;
    }
    
    // type_seqを自動生成（同じtypeの最大値+1）
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
    $stmt->bind_param('sssss', $log_datetime, $source, $type, $type_seq, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'id' => $stmt->insert_id,
            'message' => 'ラジオログを追加しました'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'データベースエラー: ' . $stmt->error
        ]);
    }
    
    $stmt->close();

} elseif ($action === 'delete') {
    // ラジオログ削除
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => '無効なIDです'
        ]);
        exit;
    }
    
    $sql = "DELETE FROM radio_logs WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'ラジオログを削除しました'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'データベースエラー: ' . $stmt->error
        ]);
    }
    
    $stmt->close();

} else {
    echo json_encode([
        'success' => false,
        'error' => '無効なアクションです'
    ]);
}

$mysqli->close();
