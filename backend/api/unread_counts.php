<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $mysqli->prepare("
        SELECT t.id as thread_id, COUNT(m.id) as unread_count
        FROM threads t
        LEFT JOIN messages m ON t.id = m.thread_id
        LEFT JOIN thread_reads tr ON t.id = tr.thread_id AND tr.user_id = ?
        WHERE m.user_id != ? AND (tr.last_read_at IS NULL OR m.created_at > tr.last_read_at)
        GROUP BY t.id
    ");
    $stmt->bind_param("ii", $currentUser['id'], $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['thread_id']] = (int)$row['unread_count'];
    }
    
    echo json_encode(["success" => true, "counts" => $counts]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
