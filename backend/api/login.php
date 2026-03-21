<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For dev only
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Username and password are required"]);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, password_hash FROM users WHERE username = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
    exit;
}
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(["success" => true, "user" => ["id" => $user['id'], "username" => $username]]);
        exit;
    }
}

http_response_code(401);
echo json_encode(["error" => "Invalid username or password"]);
