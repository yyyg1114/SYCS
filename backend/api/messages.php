<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['thread_id'] ?? null;
    $content = $data['content'] ?? '';

    if (!$threadId || !$content) {
        http_response_code(400);
        echo json_encode(["error" => "Thread ID and content are required"]);
        exit;
    }

    $stmt = $mysqli->prepare("INSERT INTO messages (thread_id, user_id, content) VALUES (?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Database error or missing table"]);
        exit;
    }
    $stmt->bind_param("iis", $threadId, $currentUser['id'], $content);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => ["id" => $stmt->insert_id, "content" => $content]]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to send message"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $threadId = $_GET['thread_id'] ?? null;
    if (!$threadId) {
        http_response_code(400);
        echo json_encode(["error" => "Thread ID is required"]);
        exit;
    }

    $stmt = $mysqli->prepare("SELECT m.id, m.content, m.created_at, u.username FROM messages m JOIN users u ON m.user_id = u.id WHERE m.thread_id = ? ORDER BY m.created_at ASC");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode(["success" => true, "messages" => $messages]);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = $data['message_id'] ?? null;
    $content = $data['content'] ?? '';

    if (!$messageId || !$content) {
        http_response_code(400);
        echo json_encode(["error" => "Message ID and content are required"]);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE messages SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $content, $messageId, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Failed to edit message or unauthorized"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    // DELETE requests can contain a body in some configurations, but we can also check GET parameters if preferred.
    // Assuming JSON body for simplicity.
    $messageId = $data['message_id'] ?? null;
    
    if (!$messageId) {
        http_response_code(400);
        echo json_encode(["error" => "Message ID is required"]);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $messageId, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Failed to delete message or unauthorized"]);
    }
}
