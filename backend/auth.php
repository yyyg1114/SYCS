<?php
// backend/auth.php
session_start();
require_once __DIR__ . '/db.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }
}

function getCurrentUser()
{
    global $mysqli;
    if (!isLoggedIn()) return null;

    $userId = $_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE id=?");
    if (!$stmt) return null;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
