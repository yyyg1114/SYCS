<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = $data['message_id'] ?? null;
    $emoji = $data['emoji'] ?? null;
    
    if (!$messageId || !$emoji) {
        http_response_code(400); 
        echo json_encode(["error" => "Params missing"]); 
        exit;
    }
    
    $checkStmt = $mysqli->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
    $checkStmt->bind_param("iis", $messageId, $currentUser['id'], $emoji);
    $checkStmt->execute();
    $res = $checkStmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $del = $mysqli->prepare("DELETE FROM message_reactions WHERE id = ?");
        $del->bind_param("i", $row['id']);
        $del->execute();
    } else {
        $ins = $mysqli->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $messageId, $currentUser['id'], $emoji);
        $ins->execute();
    }
    echo json_encode(["success" => true]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
