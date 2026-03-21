<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "All fields are required"]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Database error or table missing"]);
    exit;
}
$stmt->bind_param("sss", $username, $email, $hash);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    $_SESSION['user_id'] = $userId;
    echo json_encode(["success" => true, "user" => ["id" => $userId, "username" => $username]]);
} else {
    http_response_code(409);
    echo json_encode(["error" => "Username or email already exists"]);
}
