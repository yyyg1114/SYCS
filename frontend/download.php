<?php
// Secure Download Endpoint
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

// Filename is expected to be uuid.ext
$filename = basename($file);
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Define paths
// Primary public uploads
$publicDir = __DIR__ . '/uploads/';
$targetPath = $publicDir . $filename;

// If not in public, check protected (for SVGs that were converted, or other sensitive files if any)
// The user request implies we are downloading the files we see in chat.
// In index.php, we save to 'frontend/uploads/' ($publicDir).
// If it was an SVG, we saved the PNG version to uploads/ and the original SVG to protected_uploads/.
// If the user requests the .svg specifically (from the "Download Original" link logic we seemingly added or might add), we look there.
// But for general files (mp4, mp3), they are in uploads/.

$isProtected = false;
if (!file_exists($targetPath)) {
    // Check protected
    $protectedDir = __DIR__ . '/../protected_uploads/';
    $targetPath = $protectedDir . $filename;
    $isProtected = true;
    
    if (!file_exists($targetPath)) {
        http_response_code(404);
        die('File not found');
    }
}

// Ownership/Access Check
require_once __DIR__ . '/../backend/db.php';

$userId = $_SESSION['user_id'];

// The DB attachment_path stores 'frontend/uploads/uuid.ext' or similar.
// We need to match what is in the DB.
// If it's a generated PNG from SVG, the DB has 'frontend/uploads/uuid.png'.
// If we are downloading 'uuid.svg', we need to check if we have rights to 'frontend/uploads/uuid.png'?
// Construct potential DB paths.
$dbPathPublic = 'frontend/uploads/' . $filename;
// If checking for an SVG source, we assume the permission comes from the visible PNG in chat.
// So if requesting 'uuid.svg', check if user has access to 'uuid.png' (or 'uuid.svg' if we stored that? No, we convert).
// The logic in index.php for SVG:
// 1. Save SVG to protected_uploads/uuid.svg
// 2. Convert to PNG -> uploads/uuid.png
// 3. DB stores attachment_path = 'frontend/uploads/uuid.png'
// So if we request uuid.svg, we should check ownership of 'frontend/uploads/uuid.png'.

$dbPathToCheck = $dbPathPublic;
if ($isProtected && $ext === 'svg') {
    // If we are downloading the confidential SVG, verification is against the public PNG that represents it in chat.
    $pngName = pathinfo($filename, PATHINFO_FILENAME) . '.png';
    $dbPathToCheck = 'frontend/uploads/' . $pngName;
}

$isAuthorized = false;

// 1. Check Private DMs
$stmt = $mysqli->prepare("SELECT id FROM direct_messages WHERE attachment_path = ? AND (sender_id = ? OR receiver_id = ?)");
$stmt->bind_param("sii", $dbPathToCheck, $userId, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $isAuthorized = true;
}
$stmt->close();

// 2. Check Public Threads (If not found in DM)
if (!$isAuthorized) {
    $stmt = $mysqli->prepare("SELECT id FROM messages WHERE attachment_path = ?");
    $stmt->bind_param("s", $dbPathToCheck);
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
// Helper to get mime type
$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
    'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'm4a' => 'audio/mp4',
    'mov' => 'video/quicktime', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska', 'mp4' => 'video/mp4',
    'pdf' => 'application/pdf', 'txt' => 'text/plain', 'zip' => 'application/zip'
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($targetPath));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($targetPath);
exit;
