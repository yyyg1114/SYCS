<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400); 
        echo json_encode(["error" => "No file uploaded"]); 
        exit;
    }
    
    $file = $_FILES['file'];
    $uploadDir = __DIR__ . '/../../frontend/public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('upload_') . '.' . $ext;
    $dest = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(["success" => true, "url" => "/uploads/" . $filename]);
    } else {
        http_response_code(500); 
        echo json_encode(["error" => "Failed to move uploaded file"]);
    }
} else {
    http_response_code(405); 
    echo json_encode(["error" => "Method not allowed"]);
}
