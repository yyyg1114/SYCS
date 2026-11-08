<?php
// Secure Download Endpoint for uploaded attachments
require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die('Forbidden: Login Required');
}

function hasAttachmentAccess($mysqli, int $userId, string $file): bool
{
    $normalized = trim(str_replace('\\', '/', $file));
    $normalized = ltrim($normalized, '/');
    if ($normalized === '' || preg_match('/(^|\/)\.\.(\/|$)/', $normalized) || str_contains($normalized, "\0")) {
        return false;
    }

    $groupStmt = $mysqli->prepare(
        "SELECT 1
         FROM messages m
         JOIN group_thread_participants p ON p.thread_id = m.group_thread_id
         WHERE m.attachment_path = ? AND p.user_id = ?
         LIMIT 1"
    );
    if (!$groupStmt) {
        throw new \RuntimeException('Attachment authorization query preparation failed: ' . $mysqli->error);
    }
    $groupStmt->bind_param('si', $normalized, $userId);
    if (!$groupStmt->execute()) {
        $error = $groupStmt->error;
        $groupStmt->close();
        throw new \RuntimeException('Attachment authorization query failed: ' . $error);
    }
    $groupStmt->store_result();
    $hasGroupAccess = $groupStmt->num_rows > 0;
    $groupStmt->close();
    if ($hasGroupAccess) {
        return true;
    }

    $queries = [
        [
            "SELECT 1 FROM messages m
             JOIN threads t ON t.id = m.thread_id
             WHERE m.attachment_path = ? AND m.group_thread_id IS NULL
             LIMIT 1",
            [$normalized]
        ],
        [
            "SELECT 1 FROM direct_messages dm
             WHERE dm.attachment_path = ? AND (dm.sender_id = ? OR dm.receiver_id = ?)
             AND NOT EXISTS (
                 SELECT 1 FROM blocked_users b
                 WHERE (b.blocker_id = dm.sender_id AND b.blocked_id = dm.receiver_id)
                    OR (b.blocker_id = dm.receiver_id AND b.blocked_id = dm.sender_id)
             )
             LIMIT 1",
            [$normalized, $userId, $userId]
        ],
    ];

    foreach ($queries as [$sql, $params]) {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Attachment authorization query preparation failed: ' . $mysqli->error);
        }

        $types = '';
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : 's';
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new \RuntimeException('Attachment authorization query failed: ' . $error);
        }
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return true;
        }
        $stmt->close();
    }

    return false;
}

$file = trim((string)($_GET['file'] ?? ''));
$file = str_replace('\\', '/', $file);

if ($file === '' || preg_match('#(^|/)\.\.($|/)#', $file) || strpos($file, "\0") !== false) {
    http_response_code(400);
    die('Invalid file path');
}

if (!preg_match('#^(?:uploads/|protected_uploads/)?[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*$#', $file)) {
    http_response_code(400);
    die('Invalid file path');
}

$userId = (int)$_SESSION['user_id'];
try {
    $hasAccess = hasAttachmentAccess($mysqli, $userId, $file);
} catch (\Throwable $e) {
    error_log('Attachment authorization failed: ' . $e);
    http_response_code(500);
    die('Internal Server Error');
}
if (!$hasAccess) {
    http_response_code(403);
    die('Forbidden: Access denied');
}

$uploadsDir = __DIR__ . '/uploads/';
$protectedDir = __DIR__ . '/../protected_uploads/';

$targetPath = null;
$downloadName = null;
$mime = 'application/octet-stream';

if (preg_match('#^protected_uploads/#', $file)) {
    $uuid = pathinfo(basename($file), PATHINFO_FILENAME);
    $targetPath = $protectedDir . $uuid . '.svg';
    $downloadName = $uuid . '.svg';
    $mime = 'image/svg+xml';
} else {
    $targetPath = $uploadsDir . basename($file);
    $downloadName = basename($file);
    if (file_exists($targetPath)) {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($targetPath);
        } else {
            $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
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
    }
}

if ($targetPath === null || !file_exists($targetPath)) {
    http_response_code(404);
    die('File not found');
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($targetPath));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($targetPath);
exit;
