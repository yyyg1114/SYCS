<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partnerId = $_GET['partner_id'] ?? null;
    if (!$partnerId) {
        // List people I have DMs with
        $stmt = $mysqli->prepare("
            SELECT DISTINCT u.id, u.username, u.avatar_url, u.status, u.custom_status
            FROM direct_messages dm
            JOIN users u ON (dm.sender_id = u.id OR dm.receiver_id = u.id)
            WHERE (dm.sender_id = ? OR dm.receiver_id = ?) AND u.id != ?
            ORDER BY dm.created_at DESC
        ");
        $stmt->bind_param("iii", $currentUser['id'], $currentUser['id'], $currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $partners = [];
        while ($row = $result->fetch_assoc()) $partners[] = $row;
        echo json_encode(["success" => true, "partners" => $partners]);
        exit;
    }
    
    // Get messages with specific partner
    $stmt = $mysqli->prepare("
        SELECT dm.*, u.username 
        FROM direct_messages dm
        JOIN users u ON dm.sender_id = u.id
        WHERE (dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?)
        ORDER BY dm.created_at ASC
    ");
    $stmt->bind_param("iiii", $currentUser['id'], $partnerId, $partnerId, $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) $messages[] = $row;
    
    // Mark as read
    $mysqli->query("UPDATE direct_messages SET is_read = 1 WHERE receiver_id = {$currentUser['id']} AND sender_id = " . intval($partnerId));
    
    echo json_encode(["success" => true, "messages" => $messages]);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $receiverId = $data['receiver_id'] ?? null;
    $content = $data['content'] ?? '';
    $attachment = $data['attachment_path'] ?? null;
    
    if (!$receiverId || (!$content && !$attachment)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid message data"]);
        exit;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $mysqli->error]);
        exit;
    }
    $stmt->bind_param("iiss", $currentUser['id'], $receiverId, $content, $attachment);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    }
}
