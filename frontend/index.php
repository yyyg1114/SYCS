<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Secure Session Settings
require_once __DIR__ . '/../backend/session_config.php';

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

// 2. HTTP Security Headers
SecurityUtil::sendSecurityHeaders();

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cleanup expired messages
if (isset($mysqli)) {
    $mysqli->query("DELETE FROM messages WHERE expires_at IS NOT NULL AND expires_at < NOW()");
    $mysqli->query("DELETE FROM direct_messages WHERE expires_at IS NOT NULL AND expires_at < NOW()");
}

// Ensure basic tables exist
$mysqli->query("CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
email VARCHAR(255) DEFAULT NULL,
last_thread_id INT DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS threads (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
creator_id INT DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure users table has email protection and verification columns
$mysqli->query("ALTER TABLE users MODIFY COLUMN email VARCHAR(500)"); // Increase length for encrypted email
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'email_hash'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN email_hash VARCHAR(64) NULL AFTER email");
}

$res = $mysqli->query("SHOW INDEX FROM users WHERE Key_name = 'idx_email_hash'");
if ($res->num_rows === 0) {
    $mysqli->query("CREATE INDEX idx_email_hash ON users(email_hash)");
}

$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN is_verified TINYINT DEFAULT 0");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'verification_token'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
}

// Ensure at least one user exists for foreign key constraints
$res = $mysqli->query("SELECT id FROM users WHERE id = 1");
if ($res->num_rows === 0) {
    $hashedAdminPass = password_hash('admin_pass', PASSWORD_DEFAULT);
    $email = 'admin@example.com';
    $encryptedEmail = SecurityUtil::encrypt($email);
    $emailHash = hash('sha256', $email);
    $mysqli->query("INSERT INTO users (id, email, email_hash, username, password, is_verified) VALUES (1, '$encryptedEmail', '$emailHash', 'admin', '$hashedAdminPass', 1)");
}

// Ensure ID 1 exists specifically since it's the default
$res = $mysqli->query("SELECT id FROM threads WHERE id = 1");
if ($res->num_rows === 0) {
    $mysqli->query("INSERT INTO threads (id, name, creator_id) VALUES (1, 'general', 1)");
}

$mysqli->query("CREATE TABLE IF NOT EXISTS messages (
id INT AUTO_INCREMENT PRIMARY KEY,
thread_id INT NOT NULL,
user_id INT NOT NULL,
content TEXT,
reply_to_id INT DEFAULT NULL,
attachment_path VARCHAR(255) DEFAULT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS direct_messages (
id INT AUTO_INCREMENT PRIMARY KEY,
sender_id INT NOT NULL,
receiver_id INT NOT NULL,
content TEXT,
attachment_path VARCHAR(255),
is_read BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS friends (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id_1 INT NOT NULL,
user_id_2 INT NOT NULL,
status ENUM('pending', 'accepted') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id_1) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (user_id_2) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_friendship (user_id_1, user_id_2)
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS favorites (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
thread_id INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
UNIQUE KEY unique_fav (user_id, thread_id)
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS blocked_users (
id INT AUTO_INCREMENT PRIMARY KEY,
blocker_id INT NOT NULL,
blocked_id INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_block (blocker_id, blocked_id)
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS meeting_rooms (
id INT AUTO_INCREMENT PRIMARY KEY,
thread_id INT DEFAULT NULL,
dm_partner_id INT DEFAULT NULL,
creator_id INT NOT NULL,
room_name VARCHAR(100) NOT NULL UNIQUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS signaling (
id INT AUTO_INCREMENT PRIMARY KEY,
room_id INT NOT NULL,
sender_id INT NOT NULL,
receiver_id INT NOT NULL,
type ENUM('offer', 'answer', 'candidate') NOT NULL,
content TEXT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (room_id) REFERENCES meeting_rooms(id) ON DELETE CASCADE,
FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (endpoint(255))
)");

// Group Chat Tables
$mysqli->query("CREATE TABLE IF NOT EXISTS group_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    creator_id INT NOT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS group_thread_participants (
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (thread_id, user_id),
    FOREIGN KEY (thread_id) REFERENCES group_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Extend messages table to support group_id
$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'group_thread_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN group_thread_id INT DEFAULT NULL AFTER thread_id");
    $mysqli->query("ALTER TABLE messages ADD FOREIGN KEY (group_thread_id) REFERENCES group_threads(id) ON DELETE CASCADE");
}

// Migrations for existing schemas
$mysqli->query("IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='messages' AND COLUMN_NAME='reply_to_id') THEN ALTER TABLE messages ADD COLUMN reply_to_id INT DEFAULT NULL AFTER content; END IF;");
// 実際には PHP の SHOW COLUMNS の方が確実なので以前の方式にします

$res = $mysqli->query("SHOW COLUMNS FROM threads LIKE 'creator_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE threads ADD COLUMN creator_id INT DEFAULT 1");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'last_thread_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN last_thread_id INT DEFAULT 1");
}
$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'reply_to_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN reply_to_id INT DEFAULT NULL AFTER content");
    $mysqli->query("ALTER TABLE messages ADD FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL");
}
$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'attachment_path'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL AFTER reply_to_id");
}

// Account Status Migration
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN status ENUM('online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away') DEFAULT 'online' AFTER is_verified");
}
// Always update column definition to ensure new enums are available
$mysqli->query("ALTER TABLE users MODIFY COLUMN status ENUM('online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away') DEFAULT 'online'");
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'custom_status'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN custom_status VARCHAR(100) NULL AFTER status");
}

// Profile Column Migration
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'bio'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER custom_status");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'social_links'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN social_links JSON NULL AFTER bio");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'avatar_url'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER social_links");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'banner_color'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN banner_color VARCHAR(20) DEFAULT '#6366f1' AFTER avatar_url");
}

// New Infrastructure Migrations
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'theme_preference'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN theme_preference JSON NULL AFTER banner_color");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'typing_thread_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN typing_thread_id VARCHAR(50) DEFAULT NULL AFTER theme_preference");
} else {
    // Ensure it is VARCHAR to support dm_ prefix
    $mysqli->query("ALTER TABLE users MODIFY COLUMN typing_thread_id VARCHAR(50) DEFAULT NULL");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'typing_at'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN typing_at TIMESTAMP NULL AFTER typing_thread_id");
}

$res = $mysqli->query("SHOW COLUMNS FROM threads LIKE 'category'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE threads ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER name");
}

$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'is_edited'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN is_edited TINYINT(1) DEFAULT 0 AFTER is_pinned");
}
$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'expires_at'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN expires_at DATETIME NULL AFTER is_edited");
}

$res = $mysqli->query("SHOW COLUMNS FROM direct_messages LIKE 'is_edited'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE direct_messages ADD COLUMN is_edited TINYINT(1) DEFAULT 0 AFTER is_read");
}
$res = $mysqli->query("SHOW COLUMNS FROM direct_messages LIKE 'expires_at'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE direct_messages ADD COLUMN expires_at DATETIME NULL AFTER is_edited");
}

// Discord Columns Migration
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'discord_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN discord_id VARCHAR(255) NULL AFTER banner_color");
}

$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER discord_id");
}

$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'apple_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN apple_id VARCHAR(255) NULL AFTER google_id");
}

$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'outlook_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN outlook_id VARCHAR(255) NULL AFTER apple_id");
}

$res = $mysqli->query("SHOW INDEX FROM threads WHERE Key_name = 'idx_thread_name_unique'");
if ($res->num_rows === 0) {
    // 既存の重複がある場合は調整が必要かもしれませんが、一旦 UNIQUE インデックスを追加します
    $mysqli->query("CREATE UNIQUE INDEX idx_thread_name_unique ON threads(name)");
}

// Features Migration: Pinning and Reactions
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'notification_keywords'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN notification_keywords TEXT DEFAULT NULL AFTER theme_preference");
}

$mysqli->query("CREATE TABLE IF NOT EXISTS user_notification_settings (
    user_id INT NOT NULL,
    target_type ENUM('thread', 'group', 'dm') NOT NULL,
    target_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, target_type, target_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'is_pinned'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE messages ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 AFTER attachment_path");
}

$mysqli->query("CREATE TABLE IF NOT EXISTS message_reactions (
id INT AUTO_INCREMENT PRIMARY KEY,
message_id INT NOT NULL,
user_id INT NOT NULL,
emoji VARCHAR(50) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_reaction (message_id, user_id, emoji)
)");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_locations (
    user_id INT PRIMARY KEY,
    lat DECIMAL(10, 8) NOT NULL,
    lon DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(10, 2) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('mention', 'dm', 'friend_request', 'system') NOT NULL,
    content TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Helper to send Discord Webhook
function sendDiscordWebhook($webhookUrl, $username, $content, $avatarUrl = null, $attachmentPath = null, $baseUrl = '')
{
    if (!$webhookUrl) return;

    // Use absolute URL for avatar and attachment if they exist
    if ($baseUrl) {
        if ($avatarUrl && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
        }

        if ($attachmentPath && !filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
            $absAttachment = rtrim($baseUrl, '/') . '/' . ltrim($attachmentPath, '/');
            $content .= "\n" . $absAttachment;
        }
    }

    $data = [
        'username' => $username . " (SYCS)",
        'content' => $content,
    ];
    if ($avatarUrl) $data['avatar_url'] = $avatarUrl;

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($webhookUrl, false, $context);
    if ($result === false) {
        error_log("Discord Webhook failed: $webhookUrl");
    }
}

// Helper to notify Realtime Server
function notifyRealtimeServer($type, $data)
{
    require_once __DIR__ . '/../backend/EnvLoader.php';
    $secret = getenv('REALTIME_SECRET_KEY') ?: 'SYCS_REALTIME_SECRET_TOKEN';
    $url = 'http://localhost:3000/api/notify';
    $payload = [
        'secret' => $secret,
        'type' => $type,
        'data' => $data
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        error_log("Realtime Server notification failed: $url");
    }
}

// Helper to send Push Notification
function sendPushNotification($userId, $payload)
{
    global $mysqli;
    require_once __DIR__ . '/../backend/EnvLoader.php';
    $secret = getenv('REALTIME_SECRET_KEY') ?: 'SYCS_REALTIME_SECRET_TOKEN';
    $url = 'http://localhost:3000/api/push';

    $stmt = $mysqli->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($sub = $res->fetch_assoc()) {
        $pushPayload = [
            'secret' => $secret,
            'subscription' => [
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth']
                ]
            ],
            'payload' => $payload
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($pushPayload),
                'ignore_errors' => true
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            error_log("Push Notification failed: $url");
        }
    }
}

// Helper to verify CSRF
function verify_csrf(?string $token, ?string $sessionToken)
{
    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF Token']);
        return false;
    }
    return true;
}
function verify_token(?string $token, ?string $sessionToken)
{
    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF Token']);
        return false;
    }
    return true;
}

// --- API Logic (AJAX Handlers) ---
if (isset($_GET['api'])) {
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'update_profile') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $bio = $_POST['bio'] ?? null;
        $bannerColor = $_POST['banner_color'] ?? '#6366f1';
        $status = $_POST['status'] ?? 'online';
        $removeAvatar = ($_POST['remove_avatar'] ?? 'false') === 'true';

        // Handle Avatar Deletion / Cleanup
        if ($removeAvatar || (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK)) {
            // Get current avatar to delete old file
            $pStmt = $mysqli->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $userId);
            $pStmt->execute();
            if ($row = $pStmt->get_result()->fetch_assoc()) {
                $oldPath = $row['avatar_url'];
                if ($oldPath) {
                    $fullOldPath = __DIR__ . '/' . $oldPath; // Paths are relative to frontend/
                    if (file_exists($fullOldPath)) {
                        unlink($fullOldPath);
                    }
                }
            }
            $pStmt->close();

            if ($removeAvatar) {
                $updAva = $mysqli->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $updAva->bind_param("i", $userId);
                $updAva->execute();
                $updAva->close();
            }
        }

        $social = $_POST['social_links'] ?? null;
        $themePref = $_POST['theme_preference'] ?? null;
        $keywords = $_POST['notification_keywords'] ?? null;
        $stmt = $mysqli->prepare("UPDATE users SET bio = ?, banner_color = ?, status = ?, social_links = ?, theme_preference = ?, notification_keywords = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $bio, $bannerColor, $status, $social, $themePref, $keywords, $userId);
        $stmt->execute();
        $stmt->close();

        // Handle Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../backend/SecurityUtil.php';
            $tmpName = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (SecurityUtil::validateFile($tmpName, $ext)) {
                $uuid = SecurityUtil::generateUuid();
                $uploadDir = __DIR__ . '/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $newFileName = $uuid . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    // Store path relative to web root or current script?
                    // Previous logic used 'frontend/uploads/'. If accessed from index.php in frontend/, 
                    // it should be 'uploads/avatars/' if index.php is the entry point.
                    // However, to keep consistency with existing attachment logic:
                    $avatarPath = 'uploads/avatars/' . $newFileName;
                    $upd = $mysqli->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $upd->bind_param("si", $avatarPath, $userId);
                    $upd->execute();
                }
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'push_subscribe') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $sub = json_decode(file_get_contents('php://input'), true);
        if ($sub && isset($sub['endpoint'])) {
            $stmt = $mysqli->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)");
            $stmt->bind_param("isss", $userId, $sub['endpoint'], $sub['keys']['p256dh'], $sub['keys']['auth']);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid subscription data']);
        }
        exit;
    }

    if ($action === 'update_status') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $status = $_POST['status'] ?? 'online';
        $customStatus = $_POST['custom_status'] ?? null;
        $allowed = ['online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away'];

        if (in_array($status, $allowed)) {
            $stmt = $mysqli->prepare("UPDATE users SET status = ?, custom_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $customStatus, $userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid status']);
        }
        exit;
    }

    if ($action === 'get_user_status') {
        $targetId = $_GET['user_id'] ?? 0;
        $stmt = $mysqli->prepare("SELECT status, custom_status FROM users WHERE id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode($res ?: ['status' => 'offline', 'custom_status' => null]);
        exit;
    }

    if ($action === 'get_user_profile') {
        $targetId = $_GET['user_id'] ?? 0;
        $stmt = $mysqli->prepare("SELECT id, username, status, custom_status, bio, avatar_url, banner_color FROM users WHERE id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode($res ?: ['error' => 'User not found']);
        exit;
    }

    if ($action === 'get_friends_statuses') {
        // Get statuses of all friends
        $stmt = $mysqli->prepare("
        SELECT u.id, u.status, u.custom_status
        FROM friends f
        JOIN users u ON (f.user_id_1 = u.id OR f.user_id_2 = u.id)
        WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) 
            AND f.status = 'accepted' 
            AND u.id != ?
    ");
        $stmt->bind_param("iii", $userId, $userId, $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'get_threads') {
        $res = $mysqli->query("SELECT * FROM threads ORDER BY created_at ASC");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'create_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit; // Enforce CSRF Check
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? 'General';
        if ($name) {
            // Check for duplicate name
            $cStmt = $mysqli->prepare("SELECT id FROM threads WHERE name = ?");
            $cStmt->bind_param("s", $name);
            $cStmt->execute();
            if ($cStmt->get_result()->num_rows > 0) {
                echo json_encode(['error' => 'その名前のスレッドは既に存在します']);
                $cStmt->close();
                exit;
            }
            $cStmt->close();

            $stmt = $mysqli->prepare("INSERT INTO threads (name, creator_id, category) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $userId, $category);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Name required']);
        }
        exit;
    }

    if ($action === 'edit_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? 0;
        $newName = $_POST['name'] ?? '';

        // Check for duplicate name (excluding current thread)
        if ($newName) {
            $cStmt = $mysqli->prepare("SELECT id FROM threads WHERE name = ? AND id != ?");
            $cStmt->bind_param("si", $newName, $threadId);
            $cStmt->execute();
            if ($cStmt->get_result()->num_rows > 0) {
                echo json_encode(['error' => 'その名前のスレッドは既に存在します']);
                $cStmt->close();
                exit;
            }
            $cStmt->close();
        }

        $webhook = $_POST['discord_webhook_url'] ?? null;
        $category = $_POST['category'] ?? 'General';
        $upd = $mysqli->prepare("UPDATE threads SET name = ?, discord_webhook_url = ?, category = ? WHERE id = ?");
        $upd->bind_param("sssi", $newName, $webhook, $category, $threadId);
        $upd->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? 0;

        // Delete messages first
        $delMsgs = $mysqli->prepare("DELETE FROM messages WHERE thread_id = ?");
        $delMsgs->bind_param("i", $threadId);
        $delMsgs->execute();

        $del = $mysqli->prepare("DELETE FROM threads WHERE id = ?");
        $del->bind_param("i", $threadId);
        $del->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'toggle_reaction') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $messageId = $_POST['message_id'] ?? 0;
        $emoji = $_POST['emoji'] ?? '';

        if ($messageId && $emoji) {
            // Check if already reacted
            $stmt = $mysqli->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
            $stmt->bind_param("iis", $messageId, $userId, $emoji);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                // Remove
                $del = $mysqli->prepare("DELETE FROM message_reactions WHERE id = ?");
                $del->bind_param("i", $row['id']);
                $del->execute();
            } else {
                // Add
                $ins = $mysqli->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
                $ins->bind_param("iis", $messageId, $userId, $emoji);
                $ins->execute();
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid parameters']);
        }
        exit;
    }

    if ($action === 'toggle_pin') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $messageId = $_POST['message_id'] ?? 0;
        if ($messageId) {
            $stmt = $mysqli->prepare("UPDATE messages SET is_pinned = NOT is_pinned WHERE id = ?");
            $stmt->bind_param("i", $messageId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Message ID required']);
        }
        exit;
    }

    if ($action === 'search_messages') {
        $threadId = $_GET['thread_id'] ?? null;
        $groupThreadId = $_GET['group_thread_id'] ?? null;
        $partnerId = $_GET['partner_id'] ?? null;
        $keyword = $_GET['keyword'] ?? '';
        $hasAttachment = isset($_GET['has_attachment']) && $_GET['has_attachment'] === '1';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        if ($partnerId) {
            // Search in DMs
            $sql = "SELECT dm.*, u.username, u.avatar_url 
                    FROM direct_messages dm 
                    JOIN users u ON dm.sender_id = u.id 
                    WHERE ((dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?))";
            $params = [$userId, $partnerId, $partnerId, $userId];
            $types = "iiii";
        } else {
            // Search in Threads or Groups
            $sql = "SELECT m.*, u.username, u.avatar_url 
                    FROM messages m 
                    JOIN users u ON m.user_id = u.id 
                    WHERE 1=1";
            $params = [];
            $types = "";

            if ($threadId) {
                $sql .= " AND m.thread_id = ?";
                $params[] = $threadId;
                $types .= "i";
            } elseif ($groupThreadId) {
                $sql .= " AND m.group_thread_id = ?";
                $params[] = $groupThreadId;
                $types .= "i";
            } else {
                echo json_encode(['error' => 'Thread, Group or Partner ID required']);
                exit;
            }
        }

        if ($keyword) {
            $sql .= " AND " . ($partnerId ? "dm.content" : "m.content") . " LIKE ?";
            $params[] = "%$keyword%";
            $types .= "s";
        }

        if ($hasAttachment) {
            $sql .= " AND " . ($partnerId ? "dm.attachment_path" : "m.attachment_path") . " IS NOT NULL";
        }

        if ($dateFrom) {
            $sql .= " AND " . ($partnerId ? "dm.created_at" : "m.created_at") . " >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $sql .= " AND " . ($partnerId ? "dm.created_at" : "m.created_at") . " <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }

        $sql .= " ORDER BY " . ($partnerId ? "dm.created_at" : "m.created_at") . " DESC LIMIT 50";

        if ($types) {
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $res = $mysqli->query($sql);
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        }
        exit;
    }

    if ($action === 'update_typing_status') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? null;
        $isTyping = ($_POST['is_typing'] ?? '0') === '1';

        $stmt = $mysqli->prepare("UPDATE users SET typing_thread_id = ?, typing_at = ? WHERE id = ?");
        $threadVal = $isTyping ? $threadId : null;
        $timeVal = $isTyping ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("ssi", $threadVal, $timeVal, $userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_typing_users') {
        $threadId = $_GET['thread_id'] ?? '';
        // Consider users who typing_at is within last 5 seconds
        $stmt = $mysqli->prepare("
            SELECT username FROM users 
            WHERE typing_thread_id = ? 
            AND id != ? 
            AND typing_at > (NOW() - INTERVAL 5 SECOND)
        ");
        $stmt->bind_param("si", $threadId, $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($res);
        exit;
    }

    if ($action === 'mark_dms_as_read') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $partnerId = $_POST['partner_id'] ?? 0;
        if ($partnerId) {
            $stmt = $mysqli->prepare("UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
            $stmt->bind_param("ii", $partnerId, $userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Partner ID required']);
        }
        exit;
    }

    if ($action === 'edit_message') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $messageId = $_POST['message_id'] ?? 0;
        $dmId = $_POST['dm_id'] ?? 0;
        $content = $_POST['content'] ?? '';

        if ($messageId) {
            $stmt = $mysqli->prepare("UPDATE messages SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $content, $messageId, $userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else if ($dmId) {
            $stmt = $mysqli->prepare("UPDATE direct_messages SET content = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
            $stmt->bind_param("sii", $content, $dmId, $userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid parameters']);
        }
        exit;
    }

    if ($action === 'get_attachments') {
        $threadId = $_GET['thread_id'] ?? null;
        $partnerId = $_GET['partner_id'] ?? null;

        if ($threadId) {
            $stmt = $mysqli->prepare("SELECT attachment_path FROM messages WHERE thread_id = ? AND attachment_path IS NOT NULL ORDER BY created_at DESC");
            $stmt->bind_param("i", $threadId);
        } else if ($partnerId) {
            $stmt = $mysqli->prepare("SELECT attachment_path FROM direct_messages WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND attachment_path IS NOT NULL ORDER BY created_at DESC");
            $stmt->bind_param("iiii", $userId, $partnerId, $partnerId, $userId);
        } else {
            echo json_encode([]);
            exit;
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($res);
        exit;
    }

    if ($action === 'get_messages') {
        $threadId = $_GET['thread_id'] ?? 0;
        $stmt = $mysqli->prepare("
        SELECT m.*, u.username, u.status, u.avatar_url, r.content as reply_content, ru.username as reply_username
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        LEFT JOIN messages r ON m.reply_to_id = r.id
        LEFT JOIN users ru ON r.user_id = ru.id
        WHERE m.thread_id = ? 
        ORDER BY m.created_at ASC
    ");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch reactions for each message
        foreach ($msgs as &$m) {
            $rStmt = $mysqli->prepare("SELECT emoji, user_id FROM message_reactions WHERE message_id = ?");
            $rStmt->bind_param("i", $m['id']);
            $rStmt->execute();
            $m['reactions'] = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        echo json_encode($msgs);
        $stmt->close();
        exit;
    }

    if ($action === 'get_dm_partners') {
        // Get users I have sent to OR received from
        $query = "
        SELECT DISTINCT u.id, u.username, u.status, u.custom_status, u.avatar_url 
        FROM users u
        JOIN direct_messages dm ON (u.id = dm.sender_id OR u.id = dm.receiver_id)
        WHERE (dm.sender_id = ? OR dm.receiver_id = ?) AND u.id != ?
    ";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iii", $userId, $userId, $userId);
        $stmt->execute();
        $partners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($partners);
        exit;
    }

    if ($action === 'get_all_users') {
        // Search all users to start new DM
        $res = $mysqli->query("SELECT id, username, status, custom_status, avatar_url FROM users WHERE id != $userId");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'create_group_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $name = $_POST['name'] ?? 'Group Chat';
        $participantIds = json_decode($_POST['participant_ids'] ?? '[]', true);

        $stmt = $mysqli->prepare("INSERT INTO group_threads (name, creator_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $userId);
        $stmt->execute();
        $threadId = $stmt->insert_id;

        // Add creator as participant
        $stmt = $mysqli->prepare("INSERT INTO group_thread_participants (thread_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $threadId, $userId);
        $stmt->execute();

        // Add selected participants
        foreach ($participantIds as $pId) {
            $stmt->bind_param("ii", $threadId, $pId);
            $stmt->execute();
        }

        echo json_encode(['success' => true, 'id' => $threadId]);
        exit;
    }

    if ($action === 'get_group_threads') {
        $stmt = $mysqli->prepare("
            SELECT gt.* 
            FROM group_threads gt
            JOIN group_thread_participants gtp ON gt.id = gtp.thread_id
            WHERE gtp.user_id = ?
            ORDER BY gt.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'edit_group_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? 0;
        $newName = $_POST['name'] ?? '';

        if (!$threadId || !$newName) {
            echo json_encode(['error' => 'ID and Name required']);
            exit;
        }

        $stmt = $mysqli->prepare("UPDATE group_threads SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $newName, $threadId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_group_thread') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? 0;

        if (!$threadId) {
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        // Transactions would be better but keeping it simple as per current style
        $mysqli->query("DELETE FROM messages WHERE group_thread_id = $threadId");
        $mysqli->query("DELETE FROM group_thread_participants WHERE thread_id = $threadId");
        $mysqli->query("DELETE FROM group_threads WHERE id = $threadId");

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_group_messages') {
        $threadId = $_GET['thread_id'] ?? 0;
        // Verify membership
        $stmt = $mysqli->prepare("SELECT 1 FROM group_thread_participants WHERE thread_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $threadId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $stmt = $mysqli->prepare("
            SELECT m.*, u.username, u.avatar_url 
            FROM messages m
            JOIN users u ON m.user_id = u.id
            WHERE m.group_thread_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'update_location') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $lat = $_POST['lat'] ?? null;
        $lon = $_POST['lon'] ?? null;
        $accuracy = $_POST['accuracy'] ?? null;

        if ($lat && $lon) {
            $stmt = $mysqli->prepare("INSERT INTO user_locations (user_id, lat, lon, accuracy) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE lat = VALUES(lat), lon = VALUES(lon), accuracy = VALUES(accuracy), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("iddd", $userId, $lat, $lon, $accuracy);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid coordinates']);
        }
        exit;
    }

    if ($action === 'get_user_locations') {
        // Only return locations updated in the last 15 minutes
        $res = $mysqli->query("
            SELECT ul.*, u.username, u.avatar_url 
            FROM user_locations ul
            JOIN users u ON ul.user_id = u.id
            WHERE ul.updated_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'get_direct_messages') {
        $partnerId = $_GET['partner_id'] ?? 0;
        $stmt = $mysqli->prepare("
        SELECT dm.*, u.username, u.avatar_url 
        FROM direct_messages dm
        JOIN users u ON dm.sender_id = u.id
        WHERE (dm.sender_id = ? AND dm.receiver_id = ?) 
            OR (dm.sender_id = ? AND dm.receiver_id = ?)
        ORDER BY dm.created_at ASC
    ");
        $stmt->bind_param("iiii", $userId, $partnerId, $partnerId, $userId);
        $stmt->execute();
        $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch reactions for each DM (using message_reactions table with message_id mapping to direct_messages.id)
        // Wait, the message_reactions table is for 'messages' table. I should probably support reactions for DMs too.
        // Let's check if message_reactions can be used for both or if I need another table.
        // The foreign key is to `messages(id)`. I should add `dm_id` or a separate table if I want DM reactions.
        // For now, let's focus on the 'messages' table as requested/planned.

        echo json_encode($msgs);
        exit;
    }

    if ($action === 'send_direct_message') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $receiverId = $_POST['receiver_id'] ?? 0;
        $content = $_POST['content'] ?? '';
        $attachmentPath = null;

        // Reuse file upload logic
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['attachment']['tmp_name'];
            $fileName = $_FILES['attachment']['name'];
            $fileInfo = pathinfo($fileName);
            $ext = strtolower($fileInfo['extension'] ?? '');

            require_once __DIR__ . '/../backend/SecurityUtil.php';

            if (SecurityUtil::validateFile($tmpName, $ext)) {
                $uuid = SecurityUtil::generateUuid();

                if ($ext === 'svg') {
                    // Logic for SVG: Sanitize -> Protected Storage -> Convert PNG -> Public Storage
                    $rawContent = file_get_contents($tmpName);
                    $sanitized = SecurityUtil::sanitizeSVG($rawContent);

                    if ($sanitized) {
                        // Save Original (Sanitized) to protected
                        $protectedDir = __DIR__ . '/../protected_uploads/';
                        if (!is_dir($protectedDir)) mkdir($protectedDir, 0700, true);
                        file_put_contents($protectedDir . $uuid . '.svg', $sanitized);

                        // Convert to PNG for display
                        $publicDir = __DIR__ . '/uploads/';
                        if (!is_dir($publicDir)) mkdir($publicDir, 0755, true);

                        $pngName = $uuid . '.png';
                        $publicPath = $publicDir . $pngName;

                        if (SecurityUtil::convertSvgToPng($protectedDir . $uuid . '.svg', $publicPath)) {
                            // Success: DB stores PNG path. 
                            // *Download Logic will infer SVG availability via UUID match.*
                            $attachmentPath = 'uploads/' . $pngName;
                        } else {
                            // Fallback? If conversion fails, maybe we reject or just don't show image?
                            // Rejecting is safer as requirement says "Display as PNG".
                            echo json_encode(['error' => 'SVG Conversion Failed']);
                            exit;
                        }
                    } else {
                        echo json_encode(['error' => 'Invalid SVG content']);
                        exit;
                    }
                } else {
                    // Standard File Flow
                    $uploadDir = __DIR__ . '/uploads/'; // use relative path consistency
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $newFileName = $uuid . '.' . $ext;
                    // Move
                    if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                        $attachmentPath = 'uploads/' . $newFileName;
                    }
                }
            } else {
                echo json_encode(['error' => 'Invalid file type or content']);
                exit;
            }
        }

        if (($receiverId && $content !== '') || ($receiverId && $attachmentPath)) {
            $expiresIn = $_POST['expires_in'] ?? 0;
            $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null;

            $stmt = $mysqli->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $userId, $receiverId, $content, $attachmentPath, $expiresAt);
            $stmt->execute();
            $dmId = $stmt->insert_id;

            // Notify Realtime Server
            $newDm = [
                'id' => $dmId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'content' => $content,
                'attachment_path' => $attachmentPath,
                'username' => $_SESSION['username'] ?? 'User',
                'created_at' => date('Y-m-d H:i:s')
            ];
            notifyRealtimeServer('new_dm', ['receiverId' => $receiverId, 'message' => $newDm]);

            // Notification for DM
            $notifContent = ($_SESSION['username'] ?? 'User') . "さんからDMが届きました: " . mb_strimwidth($content, 0, 50, "...");
            $link = "index.php?dm=" . $userId;
            $nStmt = $mysqli->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'dm', ?, ?)");
            $nStmt->bind_param("iss", $receiverId, $notifContent, $link);
            $nStmt->execute();
            $nStmt->close();
            notifyRealtimeServer('new_notification', ['userId' => $receiverId]);
            sendPushNotification($receiverId, [
                'title' => '新着DM: ' . ($_SESSION['username'] ?? 'User'),
                'body' => $content,
                'icon' => 'assets/img/SYCS_favicon.svg',
                'data' => ['url' => 'index.php?dm=' . $userId]
            ]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Receiver and content/attachment required']);
        }
        exit;
    }

    if ($action === 'send_message') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit; // Enforce CSRF Check
        $threadId = !empty($_POST['thread_id']) ? $_POST['thread_id'] : null;
        $groupThreadId = !empty($_POST['group_thread_id']) ? $_POST['group_thread_id'] : null;
        $content = $_POST['content'] ?? '';
        $replyToId = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;
        $attachmentPath = null;

        // Handle File Upload (ALLOWLIST approach)
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['attachment']['tmp_name'];
            $fileName = $_FILES['attachment']['name'];
            $fileInfo = pathinfo($fileName);
            $ext = strtolower($fileInfo['extension'] ?? '');

            require_once __DIR__ . '/../backend/SecurityUtil.php';

            if (SecurityUtil::validateFile($tmpName, $ext)) {
                $uuid = SecurityUtil::generateUuid();

                if ($ext === 'svg') {
                    // Logic for SVG: Sanitize -> Protected Storage -> Convert PNG -> Public Storage
                    $rawContent = file_get_contents($tmpName);
                    $sanitized = SecurityUtil::sanitizeSVG($rawContent);

                    if ($sanitized) {
                        // Save Original (Sanitized) to protected
                        $protectedDir = __DIR__ . '/../protected_uploads/';
                        if (!is_dir($protectedDir)) mkdir($protectedDir, 0700, true);
                        file_put_contents($protectedDir . $uuid . '.svg', $sanitized);

                        // Convert to PNG for display
                        $publicDir = __DIR__ . '/uploads/';
                        if (!is_dir($publicDir)) mkdir($publicDir, 0755, true);

                        $pngName = $uuid . '.png';
                        $publicPath = $publicDir . $pngName;

                        if (SecurityUtil::convertSvgToPng($protectedDir . $uuid . '.svg', $publicPath)) {
                            // Success: DB stores PNG path. 
                            $attachmentPath = 'uploads/' . $pngName;
                        } else {
                            echo json_encode(['error' => 'SVG Conversion Failed']);
                            exit;
                        }
                    } else {
                        echo json_encode(['error' => 'Invalid SVG content']);
                        exit;
                    }
                } else {
                    // Standard File Flow
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $newFileName = $uuid . '.' . $ext;

                    if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                        $attachmentPath = 'uploads/' . $newFileName;
                    }
                }
            } else {
                echo json_encode(['error' => 'Invalid file type or content']);
                exit;
            }
        }

        if ((($threadId !== null || $groupThreadId !== null) && ($content !== '' || $attachmentPath !== null))) {
            $expiresIn = $_POST['expires_in'] ?? 0;
            $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null;

            $stmt = $mysqli->prepare("INSERT INTO messages (thread_id, group_thread_id, user_id, content, reply_to_id, attachment_path, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisiss", $threadId, $groupThreadId, $userId, $content, $replyToId, $attachmentPath, $expiresAt);
            $stmt->execute();
            $msgId = $stmt->insert_id;
            $stmt->close();

            // Notify Realtime Server
            $newMsg = [
                'id' => $msgId,
                'thread_id' => $threadId,
                'group_thread_id' => $groupThreadId,
                'user_id' => $userId,
                'content' => $content,
                'attachment_path' => $attachmentPath,
                'username' => $_SESSION['username'] ?? 'User',
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($groupThreadId) {
                notifyRealtimeServer('new_group_message', ['groupThreadId' => $groupThreadId, 'message' => $newMsg]);
            } elseif ($threadId) {
                notifyRealtimeServer('new_message', ['threadId' => $threadId, 'message' => $newMsg]);
            }

            if ($groupThreadId) {
                // Group Push Notification: Notify all participants except sender
                $pStmt = $mysqli->prepare("SELECT user_id FROM group_thread_participants WHERE thread_id = ? AND user_id != ?");
                $pStmt->bind_param("ii", $groupThreadId, $userId);
                $pStmt->execute();
                $pRes = $pStmt->get_result();
                while ($pRow = $pRes->fetch_assoc()) {
                    sendPushNotification($pRow['user_id'], [
                        'title' => '新着グループメッセージ: ' . ($_SESSION['username'] ?? 'User'),
                        'body' => $content ?: ($attachmentPath ? '[添付ファイル]' : ''),
                        'icon' => 'assets/img/SYCS_favicon.svg',
                        'data' => ['url' => 'index.php?group=' . $groupThreadId]
                    ]);
                }
                $pStmt->close();
            } elseif ($threadId) {
                // Thread Push Notification: Notify all push-subscribed users (except sender)
                // In a large app, we'd filter by thread-specific subs, but here we notify all subscribers for simplicity
                $pStmt = $mysqli->prepare("SELECT DISTINCT user_id FROM push_subscriptions WHERE user_id != ?");
                $pStmt->bind_param("i", $userId);
                $pStmt->execute();
                $pRes = $pStmt->get_result();
                while ($pRow = $pRes->fetch_assoc()) {
                    sendPushNotification($pRow['user_id'], [
                        'title' => '新着メッセージ: #' . ($threadName ?? 'Thread'),
                        'body' => ($_SESSION['username'] ?? 'User') . ': ' . ($content ?: ($attachmentPath ? '[添付ファイル]' : '')),
                        'icon' => 'assets/img/SYCS_favicon.svg',
                        'data' => ['url' => 'index.php?thread=' . $threadId]
                    ]);
                }
                $pStmt->close();

                // Thread-wide Notification: Notify all previous participants except sender (if not already handled by mention or push)
                // First, get those who have spoken in this thread
                $partStmt = $mysqli->prepare("SELECT DISTINCT user_id FROM messages WHERE thread_id = ? AND user_id != ?");
                $partStmt->bind_param("ii", $threadId, $userId);
                $partStmt->execute();
                $partRes = $partStmt->get_result();
                $participants = [];
                while ($pRow = $partRes->fetch_assoc()) {
                    $participants[] = $pRow['user_id'];
                }
                $partStmt->close();

                // Get mentioned users to avoid duplicate notifications
                $mentionedUserIds = [];
                if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
                    $mentionedNames = array_unique($matches[1]);
                    foreach ($mentionedNames as $mName) {
                        $mStmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                        $mStmt->bind_param("si", $mName, $userId);
                        $mStmt->execute();
                        if ($mRow = $mStmt->get_result()->fetch_assoc()) {
                            $mentionedUserIds[] = $mRow['id'];
                            $notifContent = ($_SESSION['username'] ?? 'User') . "さんがあなたをメンションしました: " . mb_strimwidth($content, 0, 50, "...");
                            $link = "index.php?thread=" . $threadId;
                            $nStmt = $mysqli->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'mention', ?, ?)");
                            $nStmt->bind_param("iss", $mRow['id'], $notifContent, $link);
                            $nStmt->execute();
                            $nStmt->close();
                            notifyRealtimeServer('new_notification', ['userId' => $mRow['id']]);
                        }
                    }
                }

                // Notify other participants who were NOT mentioned
                foreach ($participants as $pUserId) {
                    if (!in_array($pUserId, $mentionedUserIds)) {
                        $threadTitle = $threadName ?? 'スレッド';
                        $notifContent = "#{$threadTitle} に新しいメッセージがあります: " . mb_strimwidth($content, 0, 50, "...");
                        $link = "index.php?thread=" . $threadId;
                        $nStmt = $mysqli->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'thread_message', ?, ?)");
                        $nStmt->bind_param("iss", $pUserId, $notifContent, $link);
                        $nStmt->execute();
                        $nStmt->close();
                        notifyRealtimeServer('new_notification', ['userId' => $pUserId]);
                    }
                }

                // Discord Webhook Integration
                $wStmt = $mysqli->prepare("SELECT name, discord_webhook_url FROM threads WHERE id = ?");
                $wStmt->bind_param("i", $threadId);
                $wStmt->execute();
                $wRes = $wStmt->get_result();
                if ($wRow = $wRes->fetch_assoc()) {
                    $threadName = $wRow['name'];
                    if ($wRow['discord_webhook_url']) {
                        // Get user info for webhook
                        $uStmt = $mysqli->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
                        $uStmt->bind_param("i", $userId);
                        $uStmt->execute();
                        $uRes = $uStmt->get_result();
                        if ($uRow = $uRes->fetch_assoc()) {
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
                            $baseUrl = $protocol . "://" . $host . dirname($requestUri);

                            sendDiscordWebhook($wRow['discord_webhook_url'], $uRow['username'], $content, $uRow['avatar_url'], $attachmentPath, $baseUrl);
                        }
                        $uStmt->close();
                    }
                }
                $wStmt->close();
            }

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Thread and content/attachment required']);
        }
        exit;
    }

    if ($action === 'delete_message') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit; // Enforce CSRF Check
        $msgId = $_POST['message_id'] ?? 0;
        // Verify ownership
        $stmt = $mysqli->prepare("SELECT user_id FROM messages WHERE id = ?");
        $stmt->bind_param("i", $msgId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['user_id'] == $userId) {
                // Hard delete or Soft delete? Using Hard delete for now as per plan
                $del = $mysqli->prepare("DELETE FROM messages WHERE id = ?");
                $del->bind_param("i", $msgId);
                $del->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Forbidden']);
            }
        } else {
            echo json_encode(['error' => 'Not found']);
        }
        exit;
    }

    if ($action === 'set_last_thread') {
        $threadId = $_GET['thread_id'] ?? 1;
        $stmt = $mysqli->prepare("UPDATE users SET last_thread_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $threadId, $userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        $stmt->close();
        exit;
    }

    // --- Friend System API ---

    if ($action === 'request_friend') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $targetId = $_POST['target_id'] ?? 0;
        if ($targetId == $userId) {
            echo json_encode(['error' => 'Cannot add self']);
            exit;
        }

        // Check existence
        $stmt = $mysqli->prepare("SELECT id, status FROM friends WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)");
        $stmt->bind_param("iiii", $userId, $targetId, $targetId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Already friends or pending']);
        } else {
            // Insert (user_id_1 is sender)
            $stmt = $mysqli->prepare("INSERT INTO friends (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $userId, $targetId);
            $stmt->execute();

            // Notification for Friend Request
            $notifContent = ($_SESSION['username'] ?? 'User') . "さんからフレンド申請が届きました。";
            $link = "index.php?tab=dm"; // Friend Hub
            $nStmt = $mysqli->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'friend_request', ?, ?)");
            $nStmt->bind_param("iss", $targetId, $notifContent, $link);
            $nStmt->execute();
            $nStmt->close();
            notifyRealtimeServer('new_notification', ['userId' => $targetId]);

            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'accept_friend') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $requestId = $_POST['request_id'] ?? 0;
        // Verify I am the receiver (user_id_2)
        $stmt = $mysqli->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ? AND status = 'pending'");
        $stmt->bind_param("ii", $requestId, $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid request']);
        }
        exit;
    }

    if ($action === 'get_friend_requests') {
        // Incoming requests (I am user_id_2)
        $stmt = $mysqli->prepare("
        SELECT f.id, u.username 
        FROM friends f 
        JOIN users u ON f.user_id_1 = u.id 
        WHERE f.user_id_2 = ? AND f.status = 'pending'
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'get_friends') {
        // Accepted friends, sorted by most recent conversation
        $stmt = $mysqli->prepare("
        SELECT u.id, u.username, u.status, u.custom_status, u.avatar_url, u.banner_color,
        MAX(dm.created_at) as last_msg_at
        FROM friends f
        JOIN users u ON (f.user_id_1 = u.id OR f.user_id_2 = u.id)
        LEFT JOIN direct_messages dm ON (
            (dm.sender_id = u.id AND dm.receiver_id = ?) OR 
            (dm.sender_id = ? AND dm.receiver_id = u.id)
        )
        WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) 
            AND f.status = 'accepted' 
            AND u.id != ?
        GROUP BY u.id
        ORDER BY last_msg_at DESC, u.username ASC
    ");
        $stmt->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // --- Favorites API ---

    if ($action === 'toggle_favorite') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? 0;

        // Check if exists
        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $userId, $threadId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            // Remove
            $del = $mysqli->prepare("DELETE FROM favorites WHERE id = ?");
            $del->bind_param("i", $row['id']);
            $del->execute();
            echo json_encode(['success' => true, 'status' => 'removed']);
        } else {
            // Add
            $ins = $mysqli->prepare("INSERT INTO favorites (user_id, thread_id) VALUES (?, ?)");
            $ins->bind_param("ii", $userId, $threadId);
            $ins->execute();
            echo json_encode(['success' => true, 'status' => 'added']);
        }
        exit;
    }

    if ($action === 'get_favorites') {
        $stmt = $mysqli->prepare("
        SELECT t.* 
        FROM favorites f 
        JOIN threads t ON f.thread_id = t.id 
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'check_favorite') {
        $threadId = $_GET['thread_id'] ?? 0;
        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $userId, $threadId);
        $stmt->execute();
        echo json_encode(['is_favorite' => $stmt->get_result()->num_rows > 0]);
        exit;
    }

    // --- Block System API ---

    if ($action === 'block_user') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $targetId = $_POST['target_id'] ?? 0;
        if ($targetId == $userId) {
            echo json_encode(['error' => 'Cannot block self']);
            exit;
        }
        $stmt = $mysqli->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $targetId);
        $stmt->execute();

        // Also remove friendship if exists
        $del = $mysqli->prepare("DELETE FROM friends WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)");
        $del->bind_param("iiii", $userId, $targetId, $targetId, $userId);
        $del->execute();

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'unblock_user') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $targetId = $_POST['target_id'] ?? 0;
        $stmt = $mysqli->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->bind_param("ii", $userId, $targetId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_blocked_users') {
        $stmt = $mysqli->prepare("
        SELECT u.id, u.username 
        FROM blocked_users b 
        JOIN users u ON b.blocked_id = u.id 
        WHERE b.blocker_id = ?
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'search_users') {
        $query = $_GET['q'] ?? '';
        $query = "%$query%";
        // Search by ID (exact) or Username (partial)
        // Exclude self and blocked users? 
        // Logic: Exclude self.
        $stmt = $mysqli->prepare("
        SELECT id, username FROM users 
        WHERE id != ? 
        AND (username LIKE ? OR id = ?)
        LIMIT 20
    ");
        // For ID search, simpler to just param check
        $idParam = is_numeric($_GET['q']) ? $_GET['q'] : 0;
        $stmt->bind_param("isi", $userId, $query, $idParam);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'join_meeting') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $threadId = $_POST['thread_id'] ?? null;
        $dmPartnerId = $_POST['dm_partner_id'] ?? null;

        // Generate a stable room name
        if ($threadId) {
            $roomName = "thread_" . $threadId;
            // Verify access (could add more complex logic, but baseline is "thread exists")
            // In a real app, check if user is a member or thread is public
        } else if ($dmPartnerId) {
            $ids = [$userId, $dmPartnerId];
            sort($ids);
            $roomName = "dm_" . $ids[0] . "_" . $ids[1];

            // Verify DM Partnership
            $stmt = $mysqli->prepare("SELECT id FROM friends WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)) AND status = 'accepted'");
            $stmt->bind_param("iiii", $userId, $dmPartnerId, $dmPartnerId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden: Must be friends to start DM meeting']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thread or DM partner required']);
            exit;
        }

        // Find or create room
        $stmt = $mysqli->prepare("SELECT id FROM meeting_rooms WHERE room_name = ?");
        $stmt->bind_param("s", $roomName);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();

        if (!$room) {
            $stmt = $mysqli->prepare("INSERT INTO meeting_rooms (thread_id, dm_partner_id, creator_id, room_name) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $threadId, $dmPartnerId, $userId, $roomName);
            $stmt->execute();
            $roomId = $stmt->insert_id;
        } else {
            $roomId = $room['id'];
        }

        echo json_encode(['success' => true, 'room_id' => $roomId, 'room_name' => $roomName]);
        exit;
    }

    if ($action === 'send_signaling') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $roomId = $_POST['room_id'] ?? 0;
        $receiverId = $_POST['receiver_id'] ?? 0;
        $type = $_POST['type'] ?? '';
        $content = $_POST['content'] ?? '';

        // Verify Room Membership/Access
        $stmt = $mysqli->prepare("SELECT thread_id, dm_partner_id, creator_id FROM meeting_rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();

        if (!$room) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
            exit;
        }

        // Basic check: I am creator? OR if DM, I am one of the parties?
        $isDmParty = ($room['dm_partner_id'] && ($room['dm_partner_id'] == $userId || $room['creator_id'] == $userId));
        $isThreadParty = ($room['thread_id'] !== null); // Assuming threads are accessible by logged-in users

        if (!$isDmParty && !$isThreadParty) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $stmt = $mysqli->prepare("INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $roomId, $userId, $receiverId, $type, $content);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_signaling') {
        $roomId = $_GET['room_id'] ?? 0;
        $lastId = $_GET['last_id'] ?? 0;

        // Fetch new signaling messages for ME
        $stmt = $mysqli->prepare("
        SELECT s.*, u.username as sender_name 
        FROM signaling s 
        JOIN users u ON s.sender_id = u.id 
        WHERE s.room_id = ? AND s.receiver_id = ? AND s.id > ?
        ORDER BY s.created_at ASC
    ");
        $stmt->bind_param("iii", $roomId, $userId, $lastId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($res);
        exit;
    }

    if ($action === 'toggle_mute') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $targetType = $_POST['target_type'] ?? '';
        $targetId = $_POST['target_id'] ?? 0;
        $isMuted = ($_POST['is_muted'] ?? '1') === '1';

        if ($isMuted) {
            $stmt = $mysqli->prepare("INSERT IGNORE INTO user_notification_settings (user_id, target_type, target_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $userId, $targetType, $targetId);
            $stmt->execute();
            echo json_encode(['success' => true, 'muted' => true]);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM user_notification_settings WHERE user_id = ? AND target_type = ? AND target_id = ?");
            $stmt->bind_param("isi", $userId, $targetType, $targetId);
            $stmt->execute();
            echo json_encode(['success' => true, 'muted' => false]);
        }
        exit;
    }

    if ($action === 'get_mute_statuses') {
        $stmt = $mysqli->prepare("SELECT target_type, target_id FROM user_notification_settings WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'update_notification_keywords') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $keywords = $_POST['keywords'] ?? null;
        $stmt = $mysqli->prepare("UPDATE users SET notification_keywords = ? WHERE id = ?");
        $stmt->bind_param("si", $keywords, $userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_notifications') {
        $stmt = $mysqli->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'mark_notification_read') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $notifId = $_POST['notification_id'] ?? 0;
        $stmt = $mysqli->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notifId, $userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_all_notifications_read') {
        if (!verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null)) exit;
        $stmt = $mysqli->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    // --- New: Pinned Messages List ---
    if ($action === 'get_pinned_messages') {
        $threadId = $_GET['thread_id'] ?? null;
        $groupThreadId = $_GET['group_thread_id'] ?? null;

        if ($threadId) {
            $stmt = $mysqli->prepare("
                SELECT m.*, u.username, u.avatar_url
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.thread_id = ? AND m.is_pinned = 1
                ORDER BY m.created_at DESC
            ");
            $stmt->bind_param("i", $threadId);
        } elseif ($groupThreadId) {
            $stmt = $mysqli->prepare("
                SELECT m.*, u.username, u.avatar_url
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.group_thread_id = ? AND m.is_pinned = 1
                ORDER BY m.created_at DESC
            ");
            $stmt->bind_param("i", $groupThreadId);
        } else {
            echo json_encode([]);
            exit;
        }
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // --- New: Online Users List ---
    if ($action === 'get_online_users') {
        $stmt = $mysqli->prepare("
            SELECT id, username, status, custom_status, avatar_url
            FROM users
            WHERE status IN ('online', 'busy', 'not_allowed', 'step_out', 'going_away', 'away')
            AND id != ?
            ORDER BY
                CASE status
                    WHEN 'online' THEN 1
                    WHEN 'not_allowed' THEN 2
                    WHEN 'busy' THEN 3
                    WHEN 'step_out' THEN 4
                    WHEN 'going_away' THEN 5
                    WHEN 'away' THEN 6
                    ELSE 7
                END,
                username ASC
            LIMIT 50
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // --- New: Unread DM Counts ---
    if ($action === 'get_unread_dm_counts') {
        $stmt = $mysqli->prepare("
            SELECT sender_id, COUNT(*) as unread_count
            FROM direct_messages
            WHERE receiver_id = ? AND is_read = 0
            GROUP BY sender_id
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['sender_id']] = (int)$row['unread_count'];
        }
        $total = array_sum($counts);
        echo json_encode(['counts' => $counts, 'total' => $total]);
        exit;
    }
}

// --- Auth Status Check ---
$isLoggedIn = isset($_SESSION['user']);

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];
$initialThreadId = $_SESSION['last_thread_id'] ?? 1;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($isLoggedIn) {
    $stmt = $mysqli->prepare("SELECT last_thread_id, status, custom_status, bio, avatar_url, banner_color, social_links, theme_preference FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $initialThreadId = $row['last_thread_id'] ?: 1;
        $_SESSION['last_thread_id'] = $initialThreadId;
        $currentUserStatus = $row['status'] ?: 'online';
        $currentUserCustomStatus = $row['custom_status'];
        $currentUserBio = $row['bio'];
        $currentUserAvatar = $row['avatar_url'];
        $currentUserBanner = $row['banner_color'] ?: '#6366f1';
        $currentUserSocialLinks = json_decode($row['social_links'] ?: '{}', true);
        $currentUserThemePref = json_decode($row['theme_preference'] ?: '{}', true);
    }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT * FROM threads WHERE id = ?");
    $stmt->bind_param("i", $initialThreadId);
    $stmt->execute();
    $tres = $stmt->get_result();
    $threadRow = $tres->fetch_assoc();
    $currentThreadName = $threadRow ? $threadRow['name'] : 'general';
    $currentThreadCreatorId = $threadRow ? $threadRow['creator_id'] : 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>SYCS - Shinjuku Yamabuki Chat System</title>
    <meta name="description" content="SYCS - 新宿山吹チャットシステム。リアルタイムメッセージング、ビデオ通話、グループチャット対応。">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SYCS">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="SYCS">
    <meta name="msapplication-TileColor" content="#1a1a2e">
    <meta name="msapplication-navbutton-color" content="#6366f1">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/SYCS_favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-index.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <aside id="main-sidebar" class="sidebar">
            <div class="sidebar-top">
                <div class="logo-container">
                    <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
                    <span class="logo-version" style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v1.2.6 </span>
                </div>
                <div class="sidebar-secondary">
                    <div class="release-notes">
                        <a href="../release_notes/release_notes.html" target="_blank" rel="noopener noreferrer" style="font-size: 0.8rem; margin-left: 120px; align-items: end; text-decoration: none; color: var(--text-primary); background-color: var(--accent-hover); border-radius: 4px; padding: 2px 4px;">リリースノート</a>
                    </div>
                </div>
                <nav>
                    <ul class="nav-list">
                        <li class="nav-item active" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px;" data-tab="threads">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="9" x2="20" y2="9" />
                                <line x1="4" y1="15" x2="20" y2="15" />
                                <line x1="10" y1="3" x2="8" y2="21" />
                                <line x1="16" y1="3" x2="14" y2="21" />
                            </svg>
                            <span>スレッド</span>
                        </li>
                        <li class="nav-item" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px;" data-tab="dm">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                            <span>DM</span>
                            <span id="dm-unread-badge" style="display:none; background:#ef4444; color:white; border-radius:9999px; font-size:0.65rem; font-weight:700; padding:1px 6px; margin-left:6px; min-width:18px; text-align:center;"></span>
                        </li>
                        <li class="nav-item" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px;" data-tab="favorites">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            <span>お気に入り</span>
                        </li>
                        <li class="nav-item" onclick="window.location.href='meetings.php'" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                            <span style="font-weight: 600; color: #818cf8;">Meeting (ID連携)</span>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="sidebar-bottom">
                <div class="user-block">
                    <div class="avatar-container">
                        <div class="avatar" id="global-user-avatar">
                            <?php if ($currentUserAvatar): ?>
                                <img src="<?= htmlspecialchars($currentUserAvatar ?? '') ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_substr($currentUser ?? '', 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="status-indicator status-<?= htmlspecialchars($currentUserStatus ?? 'online') ?>" id="global-status-indicator"></div>

                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($currentUser) ?></span>
                        <div class="status-select-container">
                            <select id="sidebar-status-input" class="modal-input" style="padding: 2px 4px; font-size: 0.75rem; width: auto; background-color: #2a2b2f; border: 1px solid #444; color: #fff;" onchange="updateMyStatus(this.value)">
                                <option value="online" <?= $currentUserStatus === 'online' ? 'selected' : '' ?>>連絡可能</option>
                                <option value="busy" <?= $currentUserStatus === 'busy' ? 'selected' : '' ?>>取り込み中</option>
                                <option value="not_allowed" <?= $currentUserStatus === 'not_allowed' ? 'selected' : '' ?>>応答不可</option>
                                <option value="step_out" <?= $currentUserStatus === 'step_out' ? 'selected' : '' ?>>一時退席中</option>
                                <option value="away" <?= $currentUserStatus === 'away' ? 'selected' : '' ?>>退席中</option>
                                <option value="offline" <?= $currentUserStatus === 'offline' ? 'selected' : '' ?>>オフライン表示</option>
                                <option value="going_away" <?= $currentUserStatus === 'going_away' ? 'selected' : '' ?>>外出中</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="sidebar-actions">
                    <a href="javascript:void(0)" onclick="showProfileModal()" class="action-link">設定</a>
                    <a href="?logout=1" class="action-link" style="color:#f87171;">ログアウト</a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <!-- Notifications Modal (Centered in Main Content Area) -->
            <div id="notification-overlay" class="modal-backdrop" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2000;" onclick="toggleNotificationDropdown()"></div>
            <div id="notification-dropdown" class="modal" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:400px; max-width:90vw; max-height:80vh; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:12px; box-shadow:0 20px 50px rgba(0,0,0,0.5); z-index:2001; flex-direction:column; padding:0; overflow:hidden;">
                <div style="padding:16px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03);">
                    <h4 style="margin:0; font-size:1.1rem; font-weight:600;">通知センター</h4>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <button class="action-link" onclick="markAllNotificationsRead()" style="font-size:0.8rem; color:var(--accent-color); padding:0; background:none; border:none; cursor:pointer;">全て既読にする</button>
                        <button class="icon-btn" onclick="toggleNotificationDropdown()" style="padding:4px; opacity:0.7;">✕</button>
                    </div>
                </div>
                <div id="notification-list" class="scroller" style="flex:1; overflow-y:auto; min-height:200px; padding:8px 0;">
                    <div class="empty-state" style="padding:60px 20px; text-align:center; color:var(--text-secondary);">通知はありません</div>
                </div>
            </div>

            <section id="threads-pane" class="content-pane active">
                <div class="chat-area">
                    <header class="chat-header">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="メニュー">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <div class="thread-name-clickable" onclick="toggleThreadBrowser()">
                            <button id="fav-btn" class="icon-btn" onclick="event.stopPropagation(); toggleFavorite()"
                                title="お気に入り" style="margin-right: 8px;">
                                ☆
                            </button>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="9" x2="20" y2="9" />
                                <line x1="4" y1="15" x2="20" y2="15" />
                                <line x1="10" y1="3" x2="8" y2="21" />
                                <line x1="16" y1="3" x2="14" y2="21" />
                            </svg>
                            <span
                                id="current-thread-name"><?= htmlspecialchars($currentThreadName ?? 'general') ?></span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5;">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </div>
                        <div class="thread-actions" id="thread-actions-block" style="display:flex; margin-left: auto; align-items:center; gap:8px;">
                            <div class="search-input-wrapper" style="position:relative; display:flex; align-items:center; background:rgba(0,0,0,0.2); border-radius:4px; padding:2px 8px; margin-right:8px;">
                                <input type="text" id="search-input" placeholder="検索..." style="background:transparent; border:none; color:white; font-size:0.85rem; outline:none; width:120px;" onkeydown="if(event.key==='Enter') searchMessages()">
                                <button class="icon-btn" onclick="toggleAdvancedSearch()" style="padding:2px; height:auto; background:transparent;" title="検索フィルター">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7;">
                                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                    </svg>
                                </button>
                                <button class="icon-btn" onclick="searchMessages()" style="padding:2px; height:auto; background:transparent;" title="検索">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7;">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                                <!-- Advanced Search Panel -->
                                <div id="advanced-search-panel" style="display:none; position:absolute; top:36px; right:0; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; padding:12px; width:220px; z-index:100; box-shadow:0 8px 16px rgba(0,0,0,0.4);">
                                    <div style="margin-bottom:10px;">
                                        <label style="display:flex; align-items:center; gap:8px; font-size:0.8rem; cursor:pointer;">
                                            <input type="checkbox" id="search-has-attachment"> 添付ファイルあり
                                        </label>
                                    </div>
                                    <div style="margin-bottom:10px;">
                                        <div style="font-size:0.7rem; color:var(--text-secondary); margin-bottom:4px;">開始日</div>
                                        <input type="date" id="search-date-from" style="width:100%; height:28px; font-size:0.75rem; background:var(--input-bg); border:1px solid var(--border-color); color:white; border-radius:4px; padding:0 4px;">
                                    </div>
                                    <div style="margin-bottom:10px;">
                                        <div style="font-size:0.7rem; color:var(--text-secondary); margin-bottom:4px;">終了日</div>
                                        <input type="date" id="search-date-to" style="width:100%; height:28px; font-size:0.75rem; background:var(--input-bg); border:1px solid var(--border-color); color:white; border-radius:4px; padding:0 4px;">
                                    </div>
                                    <button class="btn-primary" onclick="searchMessages(); toggleAdvancedSearch();" style="width:100%; padding:6px; font-size:0.8rem;">検索</button>
                                </div>
                            </div>
                            <button id="notif-btn" class="icon-btn" onclick="toggleNotificationDropdown(event)" title="通知センター" style="position:relative;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span id="notif-badge" style="display:none; position:absolute; top:-2px; right:-2px; background:#ef4444; color:white; border-radius:50%; width:14px; height:14px; font-size:9px; font-weight:bold; align-items:center; justify-content:center; border:1.5px solid var(--bg-primary);">0</span>
                            </button>
                            <button id="mute-btn" class="icon-btn" onclick="toggleMute()" title="通知をミュート" style="color: var(--text-secondary);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="startMeeting()" title="ビデオ会議">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="showAttachmentGallery()" title="添付ファイル一覧">
                                <img src="assets/img/files.svg" alt="ギャラリー" style="width:16px; height:16px; filter: grayscale(1) invert(1);">
                            </button>
                            <button class="icon-btn" onclick="showPinnedMessages()" title="ピン留めメッセージ一覧">
                                <span style="font-size:14px;">📌</span>
                            </button>
                            <button id="thread-edit-btn" class="icon-btn" onclick="editCurrentThread()" title="編集">
                                <img src="assets/img/edit.svg" alt="編集" style="width:16px; height:16px;">
                            </button>
                            <button id="thread-delete-btn" class="icon-btn" onclick="deleteCurrentThread()" title="削除"
                                style="color:red;"><img src="assets/img/trash.svg" alt="削除" style="width:16px; height:16px;"></button>
                        </div>
                    </header>
                    <div class="search-results-overlay" id="search-results-overlay">
                        <div class="search-results-header">
                            <span>検索結果</span>
                            <span class="close-btn" onclick="toggleSearch(false)">✕</span>
                        </div>
                        <div id="search-results-list" class="search-results-list"></div>
                    </div>
                    <div id="message-container" class="chat-messages"></div>
                    <div class="drag-overlay">ファイルをドロップしてアップロード</div>

                    <div id="reply-bar" class="reply-bar">
                        <div style="display: flex; flex-direction: column; gap: 2px; overflow: hidden;">
                            <span style="font-size: 0.75rem; opacity: 0.8;">Replying to <strong id="reply-target-name">User</strong></span>
                            <div id="reply-preview-text" style="font-size: 0.8rem; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; opacity: 0.6;">...</div>
                        </div>
                        <span class="close-btn" onclick="cancelReply()">✕</span>
                    </div>
                    <div id="upload-preview" class="upload-preview">
                        <span style="font-size:0.85rem; color:var(--text-secondary);">添付ファイル: </span>
                        <div id="preview-content"></div>
                        <span class="close-btn upload-cancel" onclick="cancelUpload()">✕</span>
                    </div>

                    <div id="pwa-install-banner-threads" class="pwa-install-banner-integrated">
                        <div class="pwa-banner-content">
                            <span class="pwa-banner-icon">📱</span>
                            <span class="pwa-banner-text">SYCSをインストール</span>
                        </div>
                        <button class="pwa-install-btn" onclick="installPWA()">追加</button>
                        <button class="pwa-close-btn" onclick="dismissInstallBanner()">✕</button>
                    </div>

                    <div id="typing-indicator" class="typing-indicator-bar" style="font-size: 0.75rem; color: var(--text-secondary); margin: 0 16px; min-height: 18px;"></div>

                    <div class="chat-input-area">
                        <div class="input-wrapper">
                            <button class="icon-btn upload-btn-plus" title="アップロード" onclick="openMediaUploadModal()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <textarea id="msg-input" class="chat-input" placeholder="メッセージを送信... (Shift+Enterで改行)"
                                rows="1" onkeydown="handleInputKey(event)" oninput="handleTyping()"></textarea>
                            <select id="self-destruct-timer" class="icon-btn" style="width: auto; font-size: 0.7rem; background: rgba(0,0,0,0.2); border: none; color: var(--text-secondary); border-radius: 4px; padding: 2px 4px;" title="自動削除">
                                <option value="0">消去なし</option>
                                <option value="60">1分後</option>
                                <option value="3600">1時間後</option>
                                <option value="86400">1日後</option>
                            </select>
                            <button onclick="sendMessage()"
                                style="background:transparent; border:none; color:var(--accent-color); cursor:pointer; padding:5px; display:flex;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13" />
                                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <aside id="thread-browser" class="thread-browser">
                    <div class="panel-header">
                        <span>サイドバー</span>
                        <div class="close-btn" onclick="toggleThreadBrowser()"><svg width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg></div>
                    </div>

                    <div class="sidebar-tabs" style="display:flex; border-bottom:1px solid var(--border-color);">
                        <button class="tab-btn active" onclick="switchSidebarTab('threads')" style="flex:1; padding:10px; background:none; border:none; color:white; cursor:pointer;">スレッド</button>
                        <button class="tab-btn" onclick="switchSidebarTab('groups')" style="flex:1; padding:10px; background:none; border:none; color:white; cursor:pointer;">グループ</button>
                    </div>

                    <div id="thread-list" class="thread-list"></div>
                    <div id="group-list" class="thread-list" style="display:none;"></div>

                    <div id="create-thread-area" class="create-thread-area" style="border-top: none;">
                        <input type="text" id="new-thread-name" class="create-input" placeholder="新スレッド名">
                        <button onclick="createThread()" class="btn-primary" style="padding:0.6rem; margin-top:5px; width:100%;">作成</button>
                    </div>

                    <div id="create-group-area" class="create-thread-area" style="border-top: none; display:none;">
                        <button onclick="showGroupCreationDialog()" class="btn-primary" style="padding:0.6rem; width:100%;">新規グループ作成</button>
                    </div>

                    <!-- Online Users Section -->
                    <div id="online-users-section" style="border-top: 1px solid var(--border-color); padding: 10px 0;">
                        <div style="padding: 8px 10px 5px; font-size:0.7rem; font-weight:700; color:var(--text-secondary); text-transform:uppercase; display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="toggleOnlineUsers()">
                            <span>オンラインユーザー</span>
                            <span id="online-users-toggle-icon" style="font-size:0.9rem;">▾</span>
                        </div>
                        <div id="online-users-list" style="max-height:200px; overflow-y:auto;"></div>
                    </div>
                </aside>

                <dialog id="group-creation-modal" class="modal"
                    style="border:none; border-radius:8px; padding:1rem; background:var(--bg-secondary); color:var(--text-primary);">
                    <h3>グループ作成</h3>
                    <input type="text" id="group-chat-name" class="chat-input" placeholder="グループ名" style="width:100%; margin-bottom:10px;">
                    <p>メンバーを選択:</p>
                    <div id="group-member-picker" style="max-height:200px; overflow-y:auto; margin-bottom:15px; border:1px solid var(--border-color); border-radius:4px; padding:5px;"></div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn-secondary" onclick="document.getElementById('group-creation-modal').close()">キャンセル</button>
                        <button class="btn-primary" onclick="submitGroupCreation()">作成</button>
                    </div>
                </dialog>
            </section>
            <section id="dm-pane" class="content-pane" style="display:none;height:100%; flex-direction:column;">
                <!-- Friend Hub (Default View) -->
                <div id="dm-hub-view" style="display:flex; flex-direction:column; height:100%;">
                    <div class="chat-header">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="メニュー">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <h3>Friend Hub</h3>
                        <div style="margin-left:auto; display:flex; gap:10px;">
                            <button class="btn-primary" onclick="showAddFriendModal()">フレンド申請</button>
                            <button class="btn-primary" onclick="showPendingRequestsModal()" id="btn-pending-req">フレンド承認</button>
                            <button class="btn-primary" onclick="showBlockedModal()" style="background-color: #333">ブロック一覧</button>
                        </div>
                    </div>
                    <div class="scroller" style="flex:1; padding:20px; overflow-y:auto;">
                        <h4 style="margin-bottom:10px; color:var(--text-secondary);">フレンドリスト (最近のやりとり順)</h4>
                        <div id="hub-friend-list" class="thread-list"></div>
                    </div>
                </div>

                <!-- DM Chat View (Hidden by default) -->
                <div id="dm-chat-view" style="display:none; flex-direction:column; height:100%;">
                    <header class="chat-header">
                        <button class="icon-btn" onclick="backToHub()" title="戻る" style="margin-right:10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="thread-info" id="current-dm-partner-info">
                            <span class="thread-icon">@</span>
                            <h3 class="thread-name" id="current-dm-partner-name">Select a user</h3>
                        </div>
                        <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                            <button class="icon-btn" onclick="startMeeting()" title="ビデオ会議">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="showAttachmentGallery()" title="添付ファイル一覧">
                                <img src="assets/img/files.svg" alt="ギャラリー" style="width:16px; height:16px; filter: grayscale(1) invert(1);">
                            </button>
                            <button class="icon-btn" onclick="blockCurrentPartner()" title="ブロック"
                                style="color:#ef4444;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <div id="dm-message-container" class="messages-container">
                        <div class="empty-state">
                            <p>メッセージを選択してください</p>
                        </div>
                    </div>

                    <div id="dm-upload-preview" class="upload-preview-bar"
                        style="display:none; padding:10px; border-bottom:1px solid var(--border-color);">
                        <div id="dm-preview-content"></div>
                        <button class="close-btn" onclick="cancelDmUpload()">×</button>
                    </div>

                    <div id="pwa-install-banner-dm" class="pwa-install-banner-integrated">
                        <div class="pwa-banner-content">
                            <span class="pwa-banner-icon">📱</span>
                            <span class="pwa-banner-text">SYCSをインストール</span>
                        </div>
                        <button class="pwa-install-btn" onclick="installPWA()">追加</button>
                        <button class="pwa-close-btn" onclick="dismissInstallBanner()">✕</button>
                    </div>

                    <div id="dm-typing-indicator" class="typing-indicator-bar" style="font-size: 0.75rem; color: var(--text-secondary); margin: 0 16px; min-height: 18px;"></div>

                    <div class="chat-input-area" id="dm-chat-area">
                        <div class="input-wrapper">
                            <button class="icon-btn upload-btn-plus" title="アップロード" onclick="openMediaUploadModal()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <textarea id="dm-msg-input" class="chat-input" placeholder="DMを送信..." rows="1"
                                onkeydown="handleDmInputKey(event)" oninput="handleTyping()"></textarea>
                            <input type="file" id="msg-file-input" hidden onchange="handleMediaUploadFiles(this.files)">
                            <select id="dm-self-destruct-timer" class="icon-btn" style="width: auto; font-size: 0.7rem; background: rgba(0,0,0,0.2); border: none; color: var(--text-secondary); border-radius: 4px; padding: 2px 4px;" title="自動削除">
                                <option value="0">消去なし</option>
                                <option value="60">1分後</option>
                                <option value="3600">1時間後</option>
                                <option value="86400">1日後</option>
                            </select>
                            <button onclick="sendDm()"
                                style="background:transparent; border:none; color:var(--accent-color); cursor:pointer;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13" />
                                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modals -->
            <dialog id="add-friend-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3>フレンド申請</h3>
                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <input type="text" id="user-search-input" class="chat-input" placeholder="ユーザーID または 名前で検索">
                        <button class="btn-primary" onclick="searchUsers()">検索</button>
                    </div>
                    <div id="user-search-results" style="max-height:300px; overflow-y:auto;"></div>
                    <button class="btn-secondary" onclick="document.getElementById('add-friend-modal').close()" style="width:100%; margin-top:10px;">閉じる</button>
                </div>
            </dialog>

            <dialog id="gallery-modal" class="modal"
                style="border:none; border-radius:12px; padding:0; background:var(--bg-color); color:var(--text-primary); width:90%; max-width:800px; max-height:80vh;">
                <div style="display:flex; flex-direction:column; height:100%;">
                    <div style="padding:16px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0;">添付ファイルギャラリー</h3>
                        <button class="close-btn" onclick="document.getElementById('gallery-modal').close()" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer;">✕</button>
                    </div>
                    <div id="gallery-content" style="flex:1; padding:20px; overflow-y:auto; display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:16px;">
                        <!-- Attachments will be loaded here -->
                    </div>
                </div>
            </dialog>

            <dialog id="pending-requests-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3>承認待ちリクエスト</h3>
                    <div id="pending-requests-list-modal" class="thread-list"
                        style="max-height:300px; overflow-y:auto;"></div>
                    <div class="modal-actions" style="margin-top:10px; text-align:right;">
                        <button class="btn-secondary"
                            onclick="document.getElementById('pending-requests-modal').close()">閉じる</button>
                    </div>
                </div>
            </dialog>

            <!-- WebRTC Meeting Modal -->
            <dialog id="meeting-modal" class="modal meeting-modal" style="border:none; border-radius:12px; padding:0; background:#000; width:100vw; height:100vh; max-width:100vw; max-height:100vh; margin:0; overflow:hidden;">
                <div class="video-grid-container" id="video-grid">
                    <!-- Local video and remote videos will be injected here -->
                </div>
                <div class="meeting-controls">
                    <button class="control-btn" id="toggle-mic" onclick="meetingManager.toggleMic()" title="マイク オン/オフ">
                        <img id="mic-icon" src="assets/img/mic.svg" alt="">
                    </button>
                    <button class="control-btn" id="toggle-video" onclick="meetingManager.toggleVideo()" title="カメラ オン/オフ">
                        <img id="video-icon" src="assets/img/camera_on.svg" alt="">
                    </button>
                    <button class="control-btn" id="toggle-screen" onclick="meetingManager.toggleScreenShare()" title="画面共有">
                        <img id="screen-icon" src="assets/img/screen_share.svg" alt="">
                    </button>
                    <button class="control-btn" id="hangup-btn" onclick="meetingManager.leave()" title="退席">
                        <img id="hangup-icon" src="assets/img/hangup.svg" alt="" color="white">
                    </button>
                </div>
            </dialog>

            <dialog id="blocked-users-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3>ブロックしているユーザー</h3>
                    <div id="blocked-users-list" class="thread-list" style="max-height:300px; overflow-y:auto;"></div>
                    <div class="modal-actions" style="margin-top:10px; text-align:right;">
                        <button class="btn-secondary"
                            onclick="document.getElementById('blocked-users-modal').close()">閉じる</button>
                    </div>
                </div>
            </dialog>

            <!-- Pinned Messages Modal -->
            <dialog id="pinned-messages-modal" class="modal"
                style="border:none; border-radius:12px; padding:0; background:var(--bg-color); color:var(--text-primary); width:90%; max-width:720px; max-height:80vh;">
                <div style="display:flex; flex-direction:column; height:100%;">
                    <div style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:var(--bg-secondary);">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:1.2rem;">📌</span>
                            <h3 style="margin:0; font-size:1rem;">ピン留めメッセージ</h3>
                        </div>
                        <button class="close-btn" onclick="document.getElementById('pinned-messages-modal').close()" style="background:none; border:none; color:var(--text-primary); font-size:1.2rem; cursor:pointer;">✕</button>
                    </div>
                    <div id="pinned-messages-list" style="flex:1; overflow-y:auto; padding:16px;">
                        <div style="text-align:center; color:var(--text-secondary); padding:40px 0;">読み込み中...</div>
                    </div>
                </div>
            </dialog>

            <!-- Keyboard Shortcuts Help Modal -->
            <dialog id="keyboard-shortcuts-modal" class="modal"
                style="border:none; border-radius:12px; padding:24px; background:var(--bg-secondary); color:var(--text-primary); width:90%; max-width:480px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0; display:flex; align-items:center; gap:8px;"><span>⌨️</span> キーボードショートカット</h3>
                    <button onclick="document.getElementById('keyboard-shortcuts-modal').close()" style="background:none; border:none; color:var(--text-primary); font-size:1.2rem; cursor:pointer;">✕</button>
                </div>
                <div style="display:grid; gap:10px;">
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Esc</kbd>
                        <span style="font-size:0.9rem;">リプライ/検索結果を閉じる</span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">/</kbd>
                        <span style="font-size:0.9rem;">メッセージ検索</span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Alt + P</kbd>
                        <span style="font-size:0.9rem;">ピン留め一覧を表示</span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Alt + Shift + ?</kbd>
                        <span style="font-size:0.9rem;">ショートカット一覧</span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Enter</kbd>
                        <span style="font-size:0.9rem;">メッセージ送信 (Shift+Enterで改行)</span>
                    </div>
                </div>
            </dialog>

            <dialog id="thread-settings-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3>スレッド設定</h3>
                    <div class="form-group" style="margin-top:1rem;">
                        <label>スレッド名</label>
                        <input type="text" id="settings-thread-name" class="chat-input" style="width:100%;" placeholder="スレッド名">
                    </div>
                    <div class="form-group" style="margin-top:1rem;">
                        <label>カテゴリー</label>
                        <input type="text" id="settings-thread-category" class="chat-input" style="width:100%;" placeholder="カテゴリー (General, 雑談など)">
                    </div>
                    <div class="form-group" style="margin-top:1rem;">
                        <label>Discord Webhook URL</label>
                        <input type="text" id="settings-thread-webhook" class="chat-input" style="width:100%;" placeholder="https://discord.com/api/webhooks/...">
                        <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:5px;">このスレッドの書き込みをDiscordに転送します。</p>
                    </div>
                    <div class="modal-actions" style="margin-top:20px; text-align:right;">
                        <button class="btn-secondary" onclick="document.getElementById('thread-settings-modal').close()">キャンセル</button>
                        <button class="btn-primary" onclick="saveThreadSettings()">保存</button>
                    </div>
                </div>
            </dialog>
            <!-- Media Upload Modal -->
            <dialog id="media-upload-modal" class="modal media-upload-modal">
                <div class="modal-content" style="min-width: 450px; max-width: 600px;">
                    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="margin:0;">ファイルを送信</h3>
                        <button class="close-btn" onclick="closeMediaUploadModal()">
                            <p style="font-size: 20px; color: #000000; font-weight: bold; margin:0; padding:0; cursor:pointer; background-color: transparent; border: none; outline: none;">✕</p>
                        </button>
                    </div>

                    <div id="media-upload-dropzone" class="upload-dropzone" onclick="document.getElementById('modal-file-input').click()">
                        <div id="media-upload-preview-container" class="upload-preview-container">
                            <div class="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 15px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p style="margin:0; color:var(--text-secondary);">クリックまたはドラッグ＆ドロップで選択</p>
                            </div>
                        </div>
                        <input type="file" id="modal-file-input" hidden onchange="handleMediaUploadFiles(this.files)">
                    </div>

                    <div class="modal-form-group" style="margin-top:20px;">
                        <label class="modal-label">メッセージ (任意)</label>
                        <textarea id="modal-content-input" class="modal-textarea" placeholder="メッセージを入力..." rows="2" style="background:var(--input-bg); border:1px solid var(--border-color); color:white; border-radius:8px; padding:12px; width:100%; resize:none;"></textarea>
                    </div>

                    <div class="modal-actions" style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
                        <button class="btn-secondary" onclick="closeMediaUploadModal()" style="padding:10px 30px;">キャンセル</button>
                    </div>
                </div>
            </dialog>
            <section id="favorites-pane" class="content-pane" style="display:none;">
                <aside class="thread-browser active"
                    style="margin-left:0; border-right:1px solid var(--border-color); display:block; position:relative;">
                    <div class="panel-header" style="justify-content: flex-start;">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="メニュー">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <div style="display:flex; align-items:center; margin-left:10px;">お気に入りスレッド</div>
                    </div>
                    <div id="fav-thread-list" class="thread-list"></div>
                </aside>
            </section>

            <!-- Profile Edit Modal -->
            <dialog id="profile-modal" class="profile-modal">
                <div class="profile-content">
                    <div class="profile-edit-form">
                        <h3 class="settings-title">ユーザー設定</h3>

                        <div class="modal-form-group">
                            <label class="modal-label">アバター画像</label>
                            <input type="file" id="edit-avatar-input" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                            <div style="display:flex; gap:8px;">
                                <button class="btn-secondary" onclick="document.getElementById('edit-avatar-input').click()">画像を選択</button>
                                <button class="btn-secondary" id="btn-remove-avatar" onclick="removeAvatarPreview()" style="color:#f87171; display: <?= $currentUserAvatar ? 'inline-block' : 'none' ?>;">削除</button>
                            </div>
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">バナー色</label>
                            <input type="color" id="edit-banner-input" class="modal-input" style="height: 40px; padding: 5px;"
                                oninput="updatePreviewBanner(this.value)" value="<?= htmlspecialchars($currentUserBanner ?? '#6366f1') ?>">
                        </div>


                        <div class="modal-form-group">
                            <label class="modal-label">Twitter/X ID (@抜き)</label>
                            <input type="text" id="edit-twitter-input" class="modal-input" placeholder="example_user"
                                value="<?= htmlspecialchars($currentUserSocialLinks['twitter'] ?? '') ?>">
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">テーマ設定</label>
                            <div class="theme-switch-group">
                                <button class="btn-theme-selector" onclick="setTheme('dark')">ダーク</button>
                                <button class="btn-theme-selector" onclick="setTheme('light')">ライト</button>
                            </div>
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">アクセントカラー</label>
                            <input type="color" id="edit-accent-input" class="modal-input" style="height: 40px; padding: 5px;"
                                oninput="updateAccentColor(this.value)" value="#6366f1">
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">GitHub Username</label>
                            <input type="text" id="edit-github-input" class="modal-input" placeholder="example_git"
                                value="<?= htmlspecialchars($currentUserSocialLinks['github'] ?? '') ?>">
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">自己紹介</label>
                            <textarea id="edit-bio-input" class="modal-textarea" placeholder="自分について書こう"
                                oninput="updatePreviewBio(this.value)"><?= htmlspecialchars($currentUserBio ?? '') ?></textarea>
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">通知キーワード (カンマ区切り)</label>
                            <input type="text" id="edit-keywords-input" class="modal-input" placeholder="メンション,緊急,重要"
                                value="<?= htmlspecialchars($currentUserData['notification_keywords'] ?? '') ?>">
                            <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:5px;">
                                指定した単語が含まれるメッセージを受信した際、ミュート設定に関わらず通知されます。
                            </p>
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">ステータス</label>
                            <select id="modal-status-input" class="modal-input" onchange="updatePreviewStatus(this.value)">
                                <option value="online" <?= $currentUserStatus === 'online' ? 'selected' : '' ?>>連絡可能</option>
                                <option value="busy" <?= $currentUserStatus === 'busy' ? 'selected' : '' ?>>取り込み中</option>
                                <option value="not_allowed" <?= $currentUserStatus === 'not_allowed' ? 'selected' : '' ?>>応答不可</option>
                                <option value="step_out" <?= $currentUserStatus === 'step_out' ? 'selected' : '' ?>>一時退席中</option>
                                <option value="away" <?= $currentUserStatus === 'away' ? 'selected' : '' ?>>退席中</option>
                                <option value="offline" <?= $currentUserStatus === 'offline' ? 'selected' : '' ?>>オフライン表示</option>
                                <option value="going_away" <?= $currentUserStatus === 'going_away' ? 'selected' : '' ?>>外出中</option>
                            </select>
                        </div>

                        <div class="settings-actions">
                            <div class="primary-actions">
                                <button class="btn-glass-secondary" onclick="document.getElementById('profile-modal').close()">キャンセル</button>
                                <button class="btn-premium-primary" onclick="saveProfile()">保存</button>
                            </div>
                            <div class="danger-zone">
                                <a href="delete_account.php" class="delete-account-link">アカウント削除</a>
                            </div>
                        </div>
                    </div>

                    <div class="profile-preview-pane">
                        <div class="discord-card">
                            <div class="discord-banner" id="preview-banner" style="background: <?= htmlspecialchars($currentUserBanner ?? '#6366f1') ?>"></div>
                            <div class="discord-avatar-wrapper">
                                <div class="discord-avatar" id="preview-avatar-container">
                                    <?php if ($currentUserAvatar): ?>
                                        <img src="<?= htmlspecialchars($currentUserAvatar ?? '') ?>" class="discord-avatar" id="preview-avatar-img">
                                    <?php else: ?>
                                        <?= strtoupper(substr($currentUser ?? '', 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="discord-status-indicator status-<?= htmlspecialchars($currentUserStatus ?? 'online') ?>" id="preview-status-indicator"></div>
                            </div>
                            <div class="discord-body">
                                <div class="discord-username"><?= htmlspecialchars($currentUser ?? '') ?></div>
                                <div class="discord-custom-status" id="preview-custom-status-text"></div>
                                <div class="discord-divider"></div>
                                <div class="discord-section-title">自己紹介</div>
                                <div class="discord-bio" id="preview-bio"><?= nl2br(htmlspecialchars($currentUserBio ?? '')) ?></div>


                                <section class="section2" id="gps-section">
                                    <h3>GPS</h3>
                                    <div id="gps-status-display" style="font-size: 0.65rem; color: var(--text-secondary); margin-top: 2px;"></div>

                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </dialog>

            <!-- User Profile View Modal -->
            <dialog id="user-profile-modal" class="profile-modal">
                <div class="profile-content user-view-content">
                    <div class="profile-preview-pane" style="width: 100%; border-left: none;">
                        <div class="discord-card" id="user-profile-card">
                            <div class="discord-banner" id="user-profile-banner"></div>
                            <div class="discord-avatar-wrapper">
                                <div class="discord-avatar" id="user-profile-avatar-container"></div>
                                <div class="discord-status-indicator" id="user-profile-status-indicator"></div>
                            </div>
                            <div class="discord-body">
                                <div class="discord-username" id="user-profile-username"></div>
                                <div class="discord-custom-status" id="user-profile-custom-status"></div>
                                <div class="discord-divider"></div>
                                <div class="discord-section-title">自己紹介</div>
                                <div class="discord-bio" id="user-profile-bio"></div>
                                <div class="discord-divider"></div>
                                <div class="discord-section-title">SNS</div>
                                <div id="user-profile-sns" class="sns-links-container"></div>

                                <div class="user-view-actions">
                                    <button class="btn-premium-primary" id="user-profile-dm-btn">メッセージを送る</button>
                                    <button class="btn-glass-secondary" onclick="document.getElementById('user-profile-modal').close()">閉じる</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </dialog>
        </main>
    </div>

    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="js/webrtc.js"></script>
    <script src="js/locate.js"></script>
    <script>
        window.SYCS_CONFIG = {
            currentThreadId: <?= (int) ($initialThreadId ?? 1) ?>,
            currentThreadCreatorId: <?= (int) ($currentThreadCreatorId ?? 0) ?>,
            currentUserId: <?= (int) $_SESSION['user_id'] ?>,
            currentUserName: <?= json_encode($currentUser) ?>,
            currentUserTheme: <?= json_encode($currentUserThemePref ?? (object) []) ?>,
            userKeywords: <?= json_encode(array_values(array_filter(array_map('trim', explode(',', $currentUserData['notification_keywords'] ?? ''))))) ?>,
            csrfToken: <?= json_encode($_SESSION['csrf_token']) ?>,
            vapidPublicKey: <?= json_encode(getenv('VAPID_PUBLIC_KEY') ?: 'BN1pSd_YbB6fni2gJ1jRDrPipOsYQlrSXXA6LusnqUuSIi9KRYOMAAHxR-xTKV-nNjybdxHwHoxn2HeDgN1guh8') ?>
        };
    </script>
    <script src="js/index.js"></script>

    <!-- PWA Installation Logic moved to integrated locations -->

    <!-- Offline Indicator -->
    <div id="offline-indicator" style="display:none; position:fixed; top:0; left:0; right:0; background:#ef4444; color:white; text-align:center; padding:6px; font-size:0.8rem; font-family:'Inter',sans-serif; z-index:10001; animation: slideDown 0.3s ease-out;">
        ⚠️ オフラインです - 一部の機能が制限されます
    </div>
</body>

</html>
