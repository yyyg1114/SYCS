<?php
// Secure Download Endpoint for SVGs (or protected files)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', 
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die('Forbidden: Login Required');
}

$file = $_GET['file'] ?? '';

// Basic prevention of directory traversal
if (strpos($file, '/') !== false || strpos($file, '\\') !== false) {
    http_response_code(400);
    die('Invalid filename');
}

// Extract UUID from filename (assuming format: uuid.png or uuid)
// Public path stored is 'frontend/uploads/uuid.png'. 
// We expect 'file' param to be the BASENAME of the public file, e.g. "uuid.png"
$uuid = pathinfo($file, PATHINFO_FILENAME);

// Targeted Protected path
$protectedDir = __DIR__ . '/../protected_uploads/';
$targetPath = $protectedDir . $uuid . '.svg';

if (!file_exists($targetPath)) {
    // If no SVG exists, maybe it was a regular file? 
    // This endpoint is specifically for downloading the "Original" of something that was converted (SVG).
    // Or we could allow downloading the public file with headers. 
    // For now, let's assume this is exclusively for retrieving the SVG source of a converted PNG.
    http_response_code(404);
    die('Original source not found');
}

// Ownership/Access Check
require_once __DIR__ . '/../backend/db.php';

$userId = $_SESSION['user_id'];
$dbPath = 'frontend/uploads/' . $uuid . '.png'; // DB stores the public PNG path

$isAuthorized = false;

// 1. Check Private DMs
$stmt = $mysqli->prepare("SELECT id FROM direct_messages WHERE attachment_path = ? AND (sender_id = ? OR receiver_id = ?)");
$stmt->bind_param("sii", $dbPath, $userId, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $isAuthorized = true;
}
$stmt->close();

// 2. Check Public Threads (If not found in DM)
if (!$isAuthorized) {
    // In this app, threads are public. If it exists in messages, it's accessible.
    // If you add private threads later, add a join to threads table and check perms.
    $stmt = $mysqli->prepare("SELECT id FROM messages WHERE attachment_path = ?");
    $stmt->bind_param("s", $dbPath);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $isAuthorized = true;
    }
    $stmt->close();
}

if (!$isAuthorized) {
    http_response_code(403);
    die('Forbidden: You do not have permission to access this file.');
}

// Serve File
$mime = 'image/svg+xml';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $uuid . '.svg"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($targetPath));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($targetPath);
exit;
