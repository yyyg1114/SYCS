<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = $data['message_id'] ?? null;
    
    if (!$messageId) {
        http_response_code(400); 
        echo json_encode(["error" => "Message ID missing"]); 
        exit;
    }
    
    $stmt = $mysqli->prepare("UPDATE messages SET is_pinned = NOT is_pinned WHERE id = ?");
    $stmt->bind_param("i", $messageId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500); 
        echo json_encode(["error" => "Failed to pin message"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
