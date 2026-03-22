<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        // Get accepted friends
        $stmt = $mysqli->prepare("
            SELECT u.id, u.username, u.status, u.custom_status, u.avatar_url 
            FROM friends f
            JOIN users u ON (f.user_id_1 = u.id OR f.user_id_2 = u.id)
            WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted' AND u.id != ?
        ");
        $stmt->bind_param("iii", $currentUser['id'], $currentUser['id'], $currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $friends = [];
        while ($row = $result->fetch_assoc()) $friends[] = $row;
        echo json_encode(["success" => true, "friends" => $friends]);
    } else if ($action === 'pending') {
        // Get pending requests sent TO me
        $stmt = $mysqli->prepare("
            SELECT f.id as request_id, u.id as user_id, u.username 
            FROM friends f
            JOIN users u ON f.user_id_1 = u.id
            WHERE f.user_id_2 = ? AND f.status = 'pending'
        ");
        $stmt->bind_param("i", $currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $pending = [];
        while ($row = $result->fetch_assoc()) $pending[] = $row;
        echo json_encode(["success" => true, "requests" => $pending]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send friend request
    $data = json_decode(file_get_contents('php://input'), true);
    $receiverId = $data['receiver_id'] ?? null;
    
    if (!$receiverId || $receiverId == $currentUser['id']) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid receiver"]);
        exit;
    }
    
    // Check if exists
    $stmt = $mysqli->prepare("SELECT id FROM friends WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)");
    $u1 = min($currentUser['id'], $receiverId);
    $u2 = max($currentUser['id'], $receiverId);
    $stmt->bind_param("iiii", $u1, $u2, $u1, $u2);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(["success" => false, "error" => "Already friends or pending request exists"]);
        exit;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO friends (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')");
    // We maintain user_id_1 as sender for 'pending' logic? 
    // Actually our pending logic above assumes user_id_1 is sender.
    $stmt->bind_param("ii", $currentUser['id'], $receiverId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Accept friend request
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['request_id'] ?? null;
    $action = $data['action'] ?? 'accept'; // 'accept' or 'reject'
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(["error" => "Request ID required"]);
        exit;
    }
    
    if ($action === 'accept') {
        $stmt = $mysqli->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ?");
        $stmt->bind_param("ii", $requestId, $currentUser['id']);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to accept or unauthorized"]);
        }
    } else {
        $stmt = $mysqli->prepare("DELETE FROM friends WHERE id = ? AND user_id_2 = ? AND status = 'pending'");
        $stmt->bind_param("ii", $requestId, $currentUser['id']);
        $stmt->execute();
        echo json_encode(["success" => true]);
    }
}
