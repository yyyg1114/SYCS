<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

if (isLoggedIn()) {
    echo json_encode(["authenticated" => true, "user" => getCurrentUser()]);
} else {
    http_response_code(401);
    echo json_encode(["authenticated" => false]);
}
