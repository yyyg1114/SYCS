<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['thread_id'] ?? null;
    
    if (!$threadId) {
        http_response_code(400); 
        echo json_encode(["error" => "Thread ID missing"]); 
        exit;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO thread_reads (user_id, thread_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_read_at = CURRENT_TIMESTAMP()");
    $stmt->bind_param("ii", $currentUser['id'], $threadId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500); 
        echo json_encode(["error" => "Failed to update read receipt"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
