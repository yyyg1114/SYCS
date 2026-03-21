<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? '';
    
    if (!$title) {
        http_response_code(400);
        echo json_encode(["error" => "Title is required"]);
        exit;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO threads (title, creator_id) VALUES (?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Database error or missing table"]);
        exit;
    }
    $stmt->bind_param("si", $title, $currentUser['id']);
    if ($stmt->execute()) {
        $threadId = $stmt->insert_id;
        echo json_encode(["success" => true, "thread" => ["id" => $threadId, "title" => $title]]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to create thread"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $mysqli->query("SELECT t.id, t.title, t.created_at, u.username as creator_name FROM threads t JOIN users u ON t.creator_id = u.id ORDER BY t.created_at DESC");
    $threads = [];
    while ($row = $result->fetch_assoc()) {
        $threads[] = $row;
    }
    echo json_encode(["success" => true, "threads" => $threads]);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['thread_id'] ?? null;
    $title = $data['title'] ?? '';

    if (!$threadId || !$title) {
        http_response_code(400);
        echo json_encode(["error" => "Thread ID and title are required"]);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE threads SET title = ? WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("sii", $title, $threadId, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Failed to edit thread or unauthorized"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['thread_id'] ?? null;

    if (!$threadId) {
        http_response_code(400);
        echo json_encode(["error" => "Thread ID is required"]);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM threads WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $threadId, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $mysqli->query("DELETE FROM messages WHERE thread_id = " . intval($threadId));
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Failed to delete thread or unauthorized"]);
    }
}
