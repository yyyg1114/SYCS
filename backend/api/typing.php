<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update current user's typing status
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['thread_id'] ?? null; // Can be string like "dm_5"
    
    $stmt = $mysqli->prepare("UPDATE users SET typing_thread_id = ?, typing_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $threadId, $currentUser['id']);
    $stmt->execute();
    echo json_encode(["success" => true]);
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get users who are typing in a specific thread
    $threadId = $_GET['thread_id'] ?? null;
    if (!$threadId) {
        echo json_encode(["success" => true, "typing_users" => []]);
        exit;
    }
    
    // Consider anyone who typed in the last 5 seconds as "typing"
    $stmt = $mysqli->prepare("
        SELECT username FROM users 
        WHERE typing_thread_id = ? AND typing_at > (NOW() - INTERVAL 5 SECOND) AND id != ?
    ");
    $stmt->bind_param("si", $threadId, $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) $users[] = $row['username'];
    
    echo json_encode(["success" => true, "typing_users" => $users]);
}
