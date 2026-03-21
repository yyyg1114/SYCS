<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $mysqli->prepare("SELECT id, username, email, status, custom_status, bio, avatar_url, banner_color, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    
    if ($profile) {
        echo json_encode(["success" => true, "profile" => $profile]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $status = $data['status'] ?? 'online';
    $custom_status = $data['custom_status'] ?? null;
    $bio = $data['bio'] ?? null;
    $bannerColor = $data['banner_color'] ?? '#6366f1';

    $allowedStatuses = ['online', 'busy', 'away', 'offline'];
    if (!in_array($status, $allowedStatuses)) {
        $status = 'online';
    }

    $stmt = $mysqli->prepare("UPDATE users SET status = ?, custom_status = ?, bio = ?, banner_color = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $status, $custom_status, $bio, $bannerColor, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update profile"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
