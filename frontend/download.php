<?php
// Secure Download Endpoint for SVGs (or protected files)
require_once __DIR__ . '/../backend/session_config.php';

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

$uploadsDir = __DIR__ . '/uploads/';
$protectedDir = __DIR__ . '/../protected_uploads/';

// 1. Check if it's an original SVG (for converted images)
$uuid = pathinfo($file, PATHINFO_FILENAME);
$targetPath = $protectedDir . $uuid . '.svg';
$mime = 'image/svg+xml';
$downloadName = $uuid . '.svg';

if (!file_exists($targetPath)) {
    // 2. If not a protected SVG, check the literal file in uploads/
    $targetPath = $uploadsDir . $file;
    if (file_exists($targetPath)) {
        $downloadName = $file;
        // Detect MIME type
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($targetPath);
        } else {
            // Simple fallback if finfo is not available
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'zip' => 'application/zip'
            ];
            $mime = $mimes[$ext] ?? 'application/octet-stream';
        }
    } else {
        http_response_code(404);
        die('File not found');
    }
}

// TODO: Ownership/Access Check
// (Authentication is already checked above)

// Serve File
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($targetPath));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($targetPath);
exit;
