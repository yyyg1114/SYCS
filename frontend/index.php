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
    }
}
function verify_token(?string $token, ?string $sessionToken)
{
    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF Token']);
    }
}

// --- API Logic (AJAX Handlers) ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'update_profile') {
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null); // Enforce CSRF Check
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
        $threadId = $_POST['thread_id'] ?? 0;
        $newName = $_POST['name'] ?? '';

        // Verify ownership
        $stmt = $mysqli->prepare("SELECT creator_id FROM threads WHERE id = ?");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['creator_id'] == $userId) {
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
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        exit;
    }

    if ($action === 'delete_thread') {
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
        $threadId = $_POST['thread_id'] ?? 0;

        // Verify ownership
        $stmt = $mysqli->prepare("SELECT creator_id FROM threads WHERE id = ?");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['creator_id'] == $userId) {
                // Delete messages first
                $delMsgs = $mysqli->prepare("DELETE FROM messages WHERE thread_id = ?");
                $delMsgs->bind_param("i", $threadId);
                $delMsgs->execute();

                $del = $mysqli->prepare("DELETE FROM threads WHERE id = ?");
                $del->bind_param("i", $threadId);
                $del->execute();
                echo json_encode(['success' => true]);
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        exit;
    }

    if ($action === 'toggle_reaction') {
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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

            // Push Notification
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null); // Enforce CSRF Check
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

            if ($threadId) {
                // Discord Webhook Integration
                $wStmt = $mysqli->prepare("SELECT discord_webhook_url FROM threads WHERE id = ?");
                $wStmt->bind_param("i", $threadId);
                $wStmt->execute();
                $wRes = $wStmt->get_result();
                if ($wRow = $wRes->fetch_assoc()) {
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null); // Enforce CSRF Check
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'accept_friend') {
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
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
        verify_csrf($_POST['csrf_token'] ?? null, $_SESSION['csrf_token'] ?? null);
        $keywords = $_POST['keywords'] ?? '';
        $stmt = $mysqli->prepare("UPDATE users SET notification_keywords = ? WHERE id = ?");
        $stmt->bind_param("si", $keywords, $userId);
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
    <style>
        /* Status Indicators */
        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .status-indicator {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--sidebar-bg, #1e1e2e);
            background-color: #94a3b8;
            /* Default offline */
        }

        .status-online {
            background-color: #6BB700;
        }

        .status-busy {
            background-color: #C50F1F;
        }

        .status-away {
            background-color: #FCD116;
        }

        .status-offline {
            background-color: #747f8d;
        }

        .status-not_allowed {
            background-color: #C50F1F;
        }

        .status-step_out {
            background-color: #FCD116;
        }

        .status-going_away {
            background-color: #e100ffff;
        }

        /* Status Dropdown */
        .status-select-container {
            position: relative;
            margin-top: 4px;
        }

        .status-select {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.75rem;
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 4px;
            outline: none;
        }

        .status-select:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .status-select option {
            background: #1e1e2e;
            color: white;
        }

        /* Discord-style Profile Modal */
        .profile-modal {
            background: #1e1e2e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 0;
            width: 800px;
            max-width: 90vw;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            top: auto;
            bottom: 20px;
        }

        .profile-modal::backdrop {
            background: rgba(0, 0, 0, 0.7);
        }

        .profile-content {
            display: flex;
            height: 650px;
            /* Increased from 500px to see more content */
        }

        .profile-edit-form {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }

        .profile-preview-pane {
            width: 340px;
            background: #2b2d31;
            padding: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Discord Card Preview */
        .discord-card {
            width: 300px;
            background: #111214;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            font-family: 'Inter', sans-serif;
        }

        .discord-banner {
            height: 60px;
            background: var(--accent-color, #6366f1);
        }

        .discord-avatar-wrapper {
            margin-top: -30px;
            margin-left: 16px;
            position: relative;
            display: inline-block;
        }

        .discord-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 6px solid #111214;
            background: #5865f2;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            font-weight: bold;
            object-fit: cover;
        }

        .discord-status-indicator {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 4px solid #111214;
        }

        .discord-body {
            padding: 16px;
        }

        .discord-username {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .discord-custom-status {
            font-size: 0.85rem;
            color: #dbdee1;
            margin-bottom: 12px;
        }

        .discord-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 12px 0;
        }

        .discord-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #b5bac1;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .discord-bio {
            font-size: 0.85rem;
            color: #dbdee1;
            line-height: 1.4;
            white-space: pre-wrap;
        }

        /* Form styling */
        .modal-form-group {
            margin-bottom: 20px;
        }

        .modal-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #b5bac1;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .modal-input,
        .modal-textarea {
            width: 100%;
            background: #1e1f22;
            border: none;
            border-radius: 4px;
            padding: 10px;
            color: #dbdee1;
            font-size: 0.9rem;
            outline: none;
        }

        .modal-textarea {
            resize: none;
            height: 80px;
        }

        .modal-input:focus,
        .modal-textarea:focus {
            background: #000;
        }

        #tac-map-container {
            border-top: 1px solid var(--border-color);
        }

        .leaflet-container {
            background: #111 !important;
        }

        .leaflet-popup-content-wrapper,
        .leaflet-popup-tip {
            background: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        .custom-div-icon {
            background: none !important;
            border: none !important;
        }

        .marker-pin {
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            background: var(--accent-color);
            position: absolute;
            transform: rotate(-45deg);
            left: 50%;
            top: 50%;
            margin: -21px 0 0 -15px;
            border: 2px solid white;
            background-size: cover;
            background-position: center;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .marker-pin::after {
            content: '';
            width: 24px;
            height: 24px;
            margin: 1px 0 0 1px;
            background: #fff;
            position: absolute;
            border-radius: 50%;
            z-index: -1;
        }

        .marker-pin.me {
            background-color: #10b981;
            box-shadow: 0 0 15px #10b981;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <aside id="main-sidebar" class="sidebar">
            <div class="sidebar-top">
                <div class="logo-container">
                    <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
                    <span class="logo-version" style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v1.1.4</span>
                </div>
                <div class="sidebar-secondary">
                    <div class="release-notes">
                        <a href="../release_notes/release_notes.html" style="font-size: 0.8rem; margin-left: 120px; align-items: end; text-decoration: none; color: var(--text-primary); background-color: var(--accent-hover); border-radius: 4px; padding: 2px 4px;">リリースノート</a>
                    </div>
                </div>
                <nav>
                    <ul class="nav-list">
                        <li class="nav-item active" data-tab="threads">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="9" x2="20" y2="9" />
                                <line x1="4" y1="15" x2="20" y2="15" />
                                <line x1="10" y1="3" x2="8" y2="21" />
                                <line x1="16" y1="3" x2="14" y2="21" />
                            </svg>
                            <span>スレッド</span>
                        </li>
                        <li class="nav-item" data-tab="dm">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                            <span>DM</span>
                            <span id="dm-unread-badge" style="display:none; background:#ef4444; color:white; border-radius:9999px; font-size:0.65rem; font-weight:700; padding:1px 6px; margin-left:6px; min-width:18px; text-align:center;"></span>
                        </li>
                        <li class="nav-item" data-tab="favorites">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            <span>お気に入り</span>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="sidebar-bottom">
                <div class="user-block">
                    <div class="avatar-container">
                        <div class="avatar" id="global-user-avatar">
                            <?php if ($currentUserAvatar): ?>
                                <img src="<?= htmlspecialchars($currentUserAvatar) ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_substr($currentUser, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="status-indicator status-<?= htmlspecialchars($currentUserStatus) ?>" id="global-status-indicator"></div>
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
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="21" x2="4" y2="14"></line>
                                        <line x1="4" y1="10" x2="4" y2="3"></line>
                                        <line x1="12" y1="21" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12" y2="3"></line>
                                        <line x1="20" y1="21" x2="20" y2="16"></line>
                                        <line x1="20" y1="12" x2="20" y2="3"></line>
                                        <line x1="1" y1="14" x2="7" y2="14"></line>
                                        <line x1="9" y1="8" x2="15" y2="8"></line>
                                        <line x1="17" y1="16" x2="23" y2="16"></line>
                                    </svg>
                                </button>
                                <button class="icon-btn" onclick="searchMessages()" style="padding:2px; height:auto; background:transparent;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
                            <button id="mute-btn" class="icon-btn" onclick="toggleMute()" title="通知をミュート" style="color: var(--text-secondary);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
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
                            <button class="icon-btn" onclick="editCurrentThread()" title="編集">
                                <img src="assets/img/edit.svg" alt="編集" style="width:16px; height:16px;">
                            </button>
                            <button class="icon-btn" onclick="deleteCurrentThread()" title="削除"
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

                    <div id="pwa-install-banner-threads" class="pwa-install-banner-integrated" style="display:none;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:1.1rem;">📱</span>
                            <span style="font-weight:600; font-size:1.1rem;">SYCSをインストール</span>
                        </div>
                        <button onclick="installPWA()" style="background:white; color:#4f46e5; border:none; padding:6px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:1rem; white-space:nowrap;">追加</button>
                        <button onclick="dismissInstallBanner()" style="background:none; border:none; color:white; cursor:pointer; font-size:1.1rem; opacity:0.7; padding:4px;">✕</button>
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

                    <div id="pwa-install-banner-dm" class="pwa-install-banner-integrated" style="display:none;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:1.1rem;">📱</span>
                            <span style="font-weight:600; font-size:1.1rem;">SYCSをインストール</span>
                        </div>
                        <button onclick="installPWA()" style="background:white; color:#4f46e5; border:none; padding:6px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:1rem; white-space:nowrap;">追加</button>
                        <button onclick="dismissInstallBanner()" style="background:none; border:none; color:white; cursor:pointer; font-size:1.1rem; opacity:0.7; padding:4px;">✕</button>
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
                        <h3 style="margin-bottom: 24px;">ユーザー設定</h3>

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
                                oninput="updatePreviewBanner(this.value)" value="<?= htmlspecialchars($currentUserBanner) ?>">
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">Twitter/X ID (@抜き)</label>
                            <input type="text" id="edit-twitter-input" class="modal-input" placeholder="example_user"
                                value="<?= htmlspecialchars($currentUserSocialLinks['twitter'] ?? '') ?>">
                        </div>

                        <div class="modal-form-group">
                            <label class="modal-label">テーマ設定</label>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-secondary" onclick="setTheme('dark')" style="flex:1;">ダーク</button>
                                <button class="btn-secondary" onclick="setTheme('light')" style="flex:1;">ライト</button>
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
                                oninput="updatePreviewBio(this.value)"><?= htmlspecialchars($currentUserBio) ?></textarea>
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

                        <div style="margin-top:32px; display:flex; flex-direction:column; gap:12px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <button class="btn-secondary" onclick="document.getElementById('profile-modal').close()" style="padding: 12px; flex: 1;">キャンセル</button>
                                <button class="btn-primary" onclick="saveProfile()" style="padding: 12px; flex: 1; font-weight: 600;">保存</button>
                            </div>
                            <div style="display:flex; justify-content: flex-end;">
                                <a href="delete_account.php" style="color:#f87171; font-size:0.8rem; text-decoration:none;">アカウント削除</a>
                            </div>
                        </div>
                    </div>

                    <div class="profile-preview-pane">
                        <div class="discord-card">
                            <div class="discord-banner" id="preview-banner" style="background: <?= htmlspecialchars($currentUserBanner) ?>"></div>
                            <div class="discord-avatar-wrapper">
                                <div class="discord-avatar" id="preview-avatar-container">
                                    <?php if ($currentUserAvatar): ?>
                                        <img src="<?= htmlspecialchars($currentUserAvatar) ?>" class="discord-avatar" id="preview-avatar-img">
                                    <?php else: ?>
                                        <?= strtoupper(substr($currentUser, 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="discord-status-indicator status-<?= htmlspecialchars($currentUserStatus) ?>" id="preview-status-indicator"></div>
                            </div>
                            <div class="discord-body">
                                <div class="discord-username"><?= htmlspecialchars($currentUser) ?></div>
                                <div class="discord-custom-status" id="preview-custom-status-text"></div>
                                <div class="discord-divider"></div>
                                <div class="discord-section-title">自己紹介</div>
                                <div class="discord-bio" id="preview-bio"><?= nl2br(htmlspecialchars($currentUserBio)) ?></div>

                                <section class="section2" id="gps-section">
                                    <h3>GPS</h3>
                                    <div id="gps-status">位置取得待機中…</div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </dialog>

            <!-- User Profile View Modal -->
            <dialog id="user-profile-modal" class="profile-modal">
                <div class="profile-content" style="max-width: 450px;">
                    <div class="profile-preview-pane" style="width: 100%;">
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
                                <div id="user-profile-sns" style="display:flex; gap:10px; margin-top:8px;"></div>
                            </div>
                        </div>
                        <div style="margin-top: 16px; display: flex; gap: 8px; margin-left: 15px;">
                            <button class="btn-primary" onclick="document.getElementById('user-profile-modal').close()" style="flex: 1;">閉じる</button>
                            <button class="btn-primary" id="user-profile-dm-btn" style="flex: 1;">DMを送る</button>
                        </div>
                    </div>
                </div>
            </dialog>
        </main>
    </div>

    <script>
        let currentThreadId = <?= (int) ($initialThreadId ?? 1) ?>;
        let currentThreadCreatorId = <?= (int) ($currentThreadCreatorId ?? 0) ?>;
        let currentThreadWebhookUrl = null;
        let currentThreadCategory = 'General';
        const currentUserId = <?= (int) $_SESSION['user_id'] ?>;
        const currentUserName = "<?= htmlspecialchars($currentUser) ?>";
        const currentUserTheme = <?= json_encode($currentUserThemePref ?? (object) []) ?>;
        let userKeywords = <?= json_encode(array_filter(array_map('trim', explode(',', $currentUserData['notification_keywords'] ?? '')))) ?>;

        // Apply theme on early load
        if (currentUserTheme.theme === 'light') document.body.classList.add('light-theme');
        if (currentUserTheme.accentColor) {
            document.documentElement.style.setProperty('--accent-color', currentUserTheme.accentColor);
            const r = parseInt(currentUserTheme.accentColor.slice(1, 3), 16);
            const g = parseInt(currentUserTheme.accentColor.slice(3, 5), 16);
            const b = parseInt(currentUserTheme.accentColor.slice(5, 7), 16);
            document.documentElement.style.setProperty('--accent-hover', `rgba(${r}, ${g}, ${b}, 0.8)`);
        }

        // DM State
        let currentPartnerId = null;
        let currentGroupThreadId = null;
        let isDmMode = false;
        let isGroupMode = false;
        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
        let replyToId = null;
        let fileToUpload = null;

        let lastMessageId = 0;
        let lastDmId = 0;
        let isWindowFocused = true;
        window.onfocus = () => {
            isWindowFocused = true;
        };
        window.onblur = () => {
            isWindowFocused = false;
        };

        // DOM Elements
        const msgInput = document.getElementById('msg-input');
        const replyBar = document.getElementById('reply-bar');
        const uploadPreview = document.getElementById('upload-preview');
        const previewContent = document.getElementById('preview-content');

        // Helper to escape HTML to prevent XSS
        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function appendMentionHighlightedText(element, text) {
            const mentionRegex = /@([a-zA-Z0-9_]+)/g;
            const raw = String(text || '');
            let lastIndex = 0;
            let match;

            while ((match = mentionRegex.exec(raw)) !== null) {
                if (match.index > lastIndex) {
                    element.appendChild(document.createTextNode(raw.slice(lastIndex, match.index)));
                }

                const mentionSpan = document.createElement('span');
                mentionSpan.className = `mention${match[1] === currentUserName ? ' mention-me' : ''}`;
                mentionSpan.textContent = match[0];
                element.appendChild(mentionSpan);

                lastIndex = mentionRegex.lastIndex;
            }

            if (lastIndex < raw.length) {
                element.appendChild(document.createTextNode(raw.slice(lastIndex)));
            }
        }

        // --- Markdown logic removed for strict security via innerText ---

        function getAvatarElement(name, status = 'none', avatarUrl = null) {
            const initial = name ? name.charAt(0).toUpperCase() : '?';
            const colors = ['#6366f1', '#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#3b82f6'];
            const colorIdx = (name ? name.length : 0) % colors.length;

            const container = document.createElement('div');
            container.className = 'avatar-container';

            const div = document.createElement('div');
            div.className = 'avatar';

            if (avatarUrl) {
                const img = document.createElement('img');
                img.src = avatarUrl;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.borderRadius = '50%';
                img.style.objectFit = 'cover';
                div.appendChild(img);
            } else {
                div.style.background = colors[colorIdx];
                div.textContent = initial;
            }

            container.appendChild(div);

            if (status !== 'none') {
                const indicator = document.createElement('div');
                indicator.className = `status-indicator status-${status}`;
                container.appendChild(indicator);
            }

            return container;
        }

        function getSkeletonLoader() {
            const container = document.createElement('div');
            container.className = 'skeleton-container';
            for (let i = 0; i < 4; i++) {
                const item = document.createElement('div');
                item.className = 'skeleton-item';

                const avatar = document.createElement('div');
                avatar.className = 'skeleton-avatar skeleton-shimmer';

                const info = document.createElement('div');
                info.className = 'skeleton-info';

                const name = document.createElement('div');
                name.className = 'skeleton-name skeleton-shimmer';

                const text1 = document.createElement('div');
                text1.className = 'skeleton-text skeleton-shimmer';

                const text2 = document.createElement('div');
                text2.className = 'skeleton-text short skeleton-shimmer';

                info.appendChild(name);
                info.appendChild(text1);
                info.appendChild(text2);

                item.appendChild(avatar);
                item.appendChild(info);
                container.appendChild(item);
            }
            return container;
        }



        async function updateMyStatus(status) {
            const body = new FormData();
            body.append('status', status);
            const res = await api('update_status', 'POST', body);
            if (res.success) {
                // Update Indicator
                const indicator = document.getElementById('global-status-indicator');
                if (indicator) indicator.className = `status-indicator status-${status}`;

                // Sync Inputs
                const sidebarInput = document.getElementById('sidebar-status-input');
                const modalInput = document.getElementById('modal-status-input');
                if (sidebarInput) {
                    sidebarInput.value = status;
                }
                if (modalInput) modalInput.value = status;
            }
        }

        async function api(path, method = 'GET', body = null) {
            const opts = {
                method
            };
            if (body) {
                // Auto-append CSRF token if body is FormData
                if (body instanceof FormData) {
                    body.append('csrf_token', csrfToken);
                }
                opts.body = body;
            }

            try {
                const res = await fetch(`index.php?api=${path}`, opts);

                // Get response text first
                const text = await res.text();

                // Try to parse as JSON
                try {
                    const json = JSON.parse(text);
                    return json;
                } catch (parseError) {
                    console.error('JSON parse error:', parseError, text);
                    return {
                        error: 'サーバーエラー: JSONパースに失敗しました',
                        details: text.substring(0, 500)
                    };
                }
            } catch (fetchError) {
                console.error('Fetch error:', fetchError);
                return {
                    error: 'ネットワークエラー: ' + fetchError.message
                };
            }
        }

        async function loadThreads() {
            const threads = await api('get_threads');
            const list = document.getElementById('thread-list');
            list.innerText = '';

            // Group by category
            const categories = {};
            threads.forEach(t => {
                const cat = t.category || 'General';
                if (!categories[cat]) categories[cat] = [];
                categories[cat].push(t);
            });

            for (const [catName, catThreads] of Object.entries(categories)) {
                const catHeader = document.createElement('div');
                catHeader.style.padding = '10px 10px 5px 10px';
                catHeader.style.fontSize = '0.75rem';
                catHeader.style.fontWeight = '700';
                catHeader.style.color = 'var(--text-secondary)';
                catHeader.style.textTransform = 'uppercase';
                catHeader.innerText = catName;
                list.appendChild(catHeader);

                catThreads.forEach(t => {
                    const item = document.createElement('div');
                    item.className = `thread-item ${!isGroupMode && !isDmMode && t.id == currentThreadId ? 'active' : ''}`;
                    item.textContent = '# ' + t.name;
                    item.onclick = () => switchThread(t.id, t.name, t.creator_id, t.discord_webhook_url, t.category);
                    list.appendChild(item);
                });
            }
            loadGroupThreads();
        }

        async function loadGroupThreads() {
            const groups = await api('get_group_threads');
            const list = document.getElementById('group-list');
            if (!list) return;
            list.innerText = '';
            groups.forEach(g => {
                const item = document.createElement('div');
                item.className = `thread-item ${isGroupMode && g.id == currentGroupThreadId ? 'active' : ''}`;
                item.textContent = '👥 ' + g.name;
                item.onclick = () => switchGroupThread(g.id, g.name);
                list.appendChild(item);
            });
        }

        function switchSidebarTab(tab) {
            const threadList = document.getElementById('thread-list');
            const groupList = document.getElementById('group-list');
            const threadCreate = document.getElementById('create-thread-area');
            const groupCreate = document.getElementById('create-group-area');
            const btns = document.querySelectorAll('.sidebar-tabs .tab-btn');

            btns.forEach(b => b.classList.remove('active'));

            if (tab === 'threads') {
                threadList.style.display = 'block';
                groupList.style.display = 'none';
                threadCreate.style.display = 'block';
                groupCreate.style.display = 'none';
                btns[0].classList.add('active');
            } else {
                threadList.style.display = 'none';
                groupList.style.display = 'block';
                threadCreate.style.display = 'none';
                groupCreate.style.display = 'block';
                btns[1].classList.add('active');
            }
        }

        async function showGroupCreationDialog() {
            const users = await api('get_all_users');
            const picker = document.getElementById('group-member-picker');
            picker.textContent = '';
            users.forEach(u => {
                const label = document.createElement('label');
                label.style.display = 'flex';
                label.style.alignItems = 'center';
                label.style.gap = '10px';
                label.style.padding = '5px';
                label.style.cursor = 'pointer';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'group_members';
                checkbox.value = String(u.id);
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(` ${u.username || ''}`));
                picker.appendChild(label);
            });
            document.getElementById('group-creation-modal').showModal();
        }

        async function submitGroupCreation() {
            const name = document.getElementById('group-chat-name').value;
            const checkboxes = document.querySelectorAll('input[name="group_members"]:checked');
            const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));

            if (!name) return alert('グループ名を入力してください');
            if (ids.length === 0) return alert('メンバーを1人以上選択してください');

            const body = new FormData();
            body.append('name', name);
            body.append('participant_ids', JSON.stringify(ids));
            body.append('csrf_token', csrfToken);
            const res = await api('create_group_thread', 'POST', body);
            if (res.success) {
                document.getElementById('group-creation-modal').close();
                await loadGroupThreads();
            }
        }

        async function switchGroupThread(id, name) {
            isGroupMode = true;
            isDmMode = false;
            currentGroupThreadId = id;
            currentThreadId = null;
            currentPartnerId = null;

            document.getElementById('current-thread-name').innerText = name;
            document.querySelectorAll('.thread-item').forEach(el => el.classList.remove('active'));

            const container = document.getElementById('message-container');
            container.innerText = '';
            container.appendChild(getSkeletonLoader());

            if (socket) {
                socket.emit('join_group_thread', id);
            }
            loadGroupMessages();
            updateMuteIcon();
        }

        async function loadGroupMessages() {
            if (!currentGroupThreadId) return;
            const msgs = await api(`get_group_messages&thread_id=${currentGroupThreadId}`);
            const container = document.getElementById('message-container');
            container.innerText = '';

            if (msgs.length === 0) {
                const div = document.createElement('div');
                div.className = 'empty-state';
                const p = document.createElement('p');
                p.textContent = 'グループメッセージはありません。';
                p.appendChild(document.createElement('br'));
                p.appendChild(document.createTextNode('新しく会話を始めましょう！'));
                div.appendChild(p);
                container.appendChild(div);
                return;
            }

            const msgMap = {};
            const roots = [];

            msgs.forEach(m => {
                m.children = [];
                msgMap[m.id] = m;
            });

            msgs.forEach(m => {
                if (m.reply_to_id && msgMap[m.reply_to_id]) {
                    msgMap[m.reply_to_id].children.push(m);
                } else {
                    roots.push(m);
                }
            });

            roots.forEach(root => renderMessageNode(root, container));

            // Notification Trigger for Groups
            const latest = msgs[msgs.length - 1];
            if (lastMessageId !== 0 && latest.id > lastMessageId && latest.user_id != currentUserId) {
                sendNotification(`新着グループメッセージ (👥 ${document.getElementById('current-thread-name').innerText})`, `${latest.username}: ${latest.content}`, 'group', currentGroupThreadId);
            }
            lastMessageId = latest.id;

            container.scrollTop = container.scrollHeight;
        }

        async function switchThread(id, name, creatorId, webhookUrl = null, category = 'General') {
            isGroupMode = false;
            isDmMode = false;
            currentThreadId = id;
            currentGroupThreadId = null;
            currentPartnerId = null;

            currentThreadCreatorId = creatorId;
            currentThreadWebhookUrl = webhookUrl;
            currentThreadCategory = category;
            updateThreadActions();
            document.getElementById('current-thread-name').innerText = name;
            document.querySelectorAll('.thread-item').forEach(el => {
                el.classList.remove('active');
                if (el.textContent === '# ' + name) el.classList.add('active');
            });

            const container = document.getElementById('message-container');
            container.innerText = '';
            container.appendChild(getSkeletonLoader());
            cancelReply();
            cancelUpload();
            loadMessages(1000);
            checkFavoriteStatus();
            updateMuteIcon();
            api(`set_last_thread&thread_id=${id}`);
        }

        function updateThreadActions() {
            const block = document.getElementById('thread-actions-block');
            if (parseInt(currentThreadId) === 1) {
                // Prevent editing/deleting General thread
                if (block) block.style.display = 'none';
                return;
            }
            if (parseInt(currentThreadCreatorId) === parseInt(currentUserId)) {
                if (block) block.style.display = 'flex';
            } else {
                if (block) block.style.display = 'none';
            }
        }

        async function editCurrentThread() {
            document.getElementById('settings-thread-name').value = document.getElementById('current-thread-name').innerText;
            document.getElementById('settings-thread-webhook').value = currentThreadWebhookUrl || '';
            document.getElementById('settings-thread-category').value = currentThreadCategory || 'General';
            document.getElementById('thread-settings-modal').showModal();
        }

        async function saveThreadSettings() {
            const newName = document.getElementById('settings-thread-name').value;
            const webhook = document.getElementById('settings-thread-webhook').value;
            const category = document.getElementById('settings-thread-category').value;

            if (newName && newName.trim() !== "") {
                const body = new FormData();
                body.append('thread_id', currentThreadId);
                body.append('name', newName.trim());
                body.append('discord_webhook_url', webhook.trim());
                body.append('category', category.trim());
                const res = await api('edit_thread', 'POST', body);
                if (res.success) {
                    document.getElementById('thread-settings-modal').close();
                    await loadThreads();
                    switchThread(currentThreadId, newName.trim(), currentThreadCreatorId, webhook.trim(), category.trim());
                } else {
                    alert("保存に失敗しました: " + (res.error || 'Unknown'));
                }
            }
        }

        async function deleteCurrentThread() {
            if (confirm("本当にこのスレッドを削除しますか？")) {
                const body = new FormData();
                body.append('thread_id', currentThreadId);
                const res = await api('delete_thread', 'POST', body);
                if (res.success) {
                    location.reload();
                } else {
                    alert("削除に失敗しました: " + (res.error || 'Unknown'));
                }
            }
        }

        // --- Profile Logic ---
        function showProfileModal() {
            document.getElementById('profile-modal').showModal();
        }

        function updatePreviewBanner(color) {
            document.getElementById('preview-banner').style.background = color;
        }

        function updatePreviewBio(text) {
            document.getElementById('preview-bio').innerText = text;
        }

        function updatePreviewStatus(status) {
            const indicator = document.getElementById('preview-status-indicator');
            indicator.className = `discord-status-indicator status-${status}`;
        }

        let shouldRemoveAvatar = false;

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                shouldRemoveAvatar = false;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('preview-avatar-container');
                    container.textContent = '';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'discord-avatar';
                    img.id = 'preview-avatar-img';
                    container.appendChild(img);
                    document.getElementById('btn-remove-avatar').style.display = 'inline-block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeAvatarPreview() {
            shouldRemoveAvatar = true;
            document.getElementById('edit-avatar-input').value = '';
            const container = document.getElementById('preview-avatar-container');
            container.textContent = currentUserName ? currentUserName.charAt(0).toUpperCase() : '?';
            container.style.background = '#6366f1';
            document.getElementById('btn-remove-avatar').style.display = 'none';
        }

        async function saveProfile() {
            const bio = document.getElementById('edit-bio-input').value;
            const banner = document.getElementById('edit-banner-input').value;
            const twitter = document.getElementById('edit-twitter-input').value;
            const github = document.getElementById('edit-github-input').value;
            const status = document.getElementById('modal-status-input').value;
            const avatarFile = document.getElementById('edit-avatar-input').files[0];

            const accentColor = document.getElementById('edit-accent-input').value;
            const theme = document.body.classList.contains('light-theme') ? 'light' : 'dark';

            const keywords = document.getElementById('edit-keywords-input').value;

            const body = new FormData();
            body.append('csrf_token', '<?= $_SESSION["csrf_token"] ?>');
            body.append('bio', bio);
            body.append('banner_color', banner);
            body.append('status', status);
            body.append('notification_keywords', keywords);
            body.append('social_links', JSON.stringify({
                twitter,
                github
            }));
            body.append('theme_preference', JSON.stringify({
                theme,
                accentColor
            }));
            body.append('remove_avatar', shouldRemoveAvatar);
            if (avatarFile) {
                body.append('avatar', avatarFile);
            }

            const res = await api('update_profile', 'POST', body);
            if (res.success) {
                alert('プロフィールを更新しました');
                location.reload(); // Simplest to reflect all changes
            } else {
                alert('更新に失敗しました: ' + (res.error || '不明なエラー'));
            }
        }

        // --- User Profile View Logic ---
        async function showUserProfile(userId, username) {
            // 自分自身の場合は自分用のモーダルを開く
            if (parseInt(userId) === currentUserId) {
                showProfileModal();
                return;
            }

            const modal = document.getElementById('user-profile-modal');
            const res = await api(`get_user_profile&user_id=${userId}`);

            if (res.error) {
                alert('ユーザー情報の取得に失敗しました');
                return;
            }

            // バナー
            document.getElementById('user-profile-banner').style.background = res.banner_color || '#6366f1';

            // アバター
            const avatarContainer = document.getElementById('user-profile-avatar-container');
            if (res.avatar_url) {
                avatarContainer.textContent = '';
                const img = document.createElement('img');
                img.src = res.avatar_url;
                img.className = 'discord-avatar';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.borderRadius = '50%';
                img.style.objectFit = 'cover';
                avatarContainer.appendChild(img);
            } else {
                const initial = res.username ? res.username.charAt(0).toUpperCase() : '?';
                avatarContainer.textContent = initial;
                avatarContainer.style.background = '#6366f1';
            }

            // ステータスインジケーター
            const statusIndicator = document.getElementById('user-profile-status-indicator');
            statusIndicator.className = `discord-status-indicator status-${res.status || 'offline'}`;

            // ユーザー名
            document.getElementById('user-profile-username').innerText = res.username;

            // カスタムステータス
            document.getElementById('user-profile-custom-status').innerText = res.custom_status || '';

            // Bio
            document.getElementById('user-profile-bio').innerText = res.bio || '自己紹介はまだありません';

            // SNS
            const snsContainer = document.getElementById('user-profile-sns');
            snsContainer.textContent = '';
            const links = JSON.parse(res.social_links || '{}');
            if (links.twitter) {
                const a = document.createElement('a');
                a.href = `https://twitter.com/${links.twitter}`;
                a.target = '_blank';
                a.style.color = '#1DA1F2';
                a.innerText = 'Twitter';
                snsContainer.appendChild(a);
            }
            if (links.github) {
                const a = document.createElement('a');
                a.href = `https://github.com/${links.github}`;
                a.target = '_blank';
                a.style.color = '#f0f6fc';
                a.innerText = 'GitHub';
                snsContainer.appendChild(a);
            }
            if (!links.twitter && !links.github) {
                snsContainer.innerText = '連携なし';
            }

            // DMボタンの設定
            const dmBtn = document.getElementById('user-profile-dm-btn');
            dmBtn.onclick = () => {
                modal.close();
                // DMタブに切り替えてチャットを開始
                document.querySelector('.nav-item[data-tab="dm"]').click();
                switchToDmChat(res.id, res.username, res.avatar_url, res.status);
            };

            modal.showModal();
        }

        async function loadMessages(minDelay = 0) {
            const startTime = Date.now();
            const messages = await api(`get_messages&thread_id=${currentThreadId}`);

            if (minDelay > 0) {
                const elapsed = Date.now() - startTime;
                const remaining = minDelay - elapsed;
                if (remaining > 0) await new Promise(r => setTimeout(r, remaining));
            }
            const container = document.getElementById('message-container');
            // Auto-scroll logic needs to be smarter or just stick to bottom if already at bottom
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

            container.innerText = ''; // Clear safely
            if (messages.length === 0) {
                const p = document.createElement('p');
                p.innerText = 'ｼｰﾝ...静かな場所ですね。\n少し世間話でもどうでしょうか?';
                const div = document.createElement('div');
                div.className = 'empty-state';
                div.appendChild(p);
                container.appendChild(div);
            } else {
                // Build Tree
                const msgMap = {};
                const roots = [];

                // 1. Init map
                messages.forEach(m => {
                    m.children = [];
                    msgMap[m.id] = m;
                });

                // 2. Assign children
                messages.forEach(m => {
                    if (m.reply_to_id && msgMap[m.reply_to_id]) {
                        msgMap[m.reply_to_id].children.push(m);
                    } else {
                        roots.push(m);
                    }
                });

                // 3. Recursive Render
                roots.forEach(root => renderMessageNode(root, container));

                // Notification Trigger
                const latest = messages[messages.length - 1];
                if (lastMessageId !== 0 && latest.id > lastMessageId && latest.user_id != currentUserId) {
                    sendNotification(`新着メッセージ (#${document.getElementById('current-thread-name').innerText})`, `${latest.username}: ${latest.content}`, 'thread', currentThreadId);
                }
                lastMessageId = latest.id;
            }

            if (isAtBottom) container.scrollTop = container.scrollHeight;
        }

        function renderMessageNode(m, parentContainer) {
            // Wrapper for indentation
            const wrapper = document.createElement('div');
            wrapper.className = 'message-wrapper';
            // If it's a child (implied by context, but we handle visual indent via nesting divs)
            // We create the message group, then a child container.

            // Add ID for jumping
            wrapper.id = 'message-' + m.id;

            const group = document.createElement('div');
            group.className = 'message-group';

            // Avatar
            group.appendChild(getAvatarElement(m.username, m.status || 'online', m.avatar_url));

            const info = document.createElement('div');
            info.className = 'message-info';

            const header = document.createElement('div');
            header.className = 'message-header';

            const user = document.createElement('span');
            user.className = 'message-user clickable-username';
            user.textContent = m.username;
            user.style.cursor = 'pointer';
            user.onclick = (e) => {
                e.stopPropagation();
                showUserProfile(m.user_id, m.username);
            };

            const time = document.createElement('span');
            time.className = 'message-time';
            time.textContent = m.created_at;

            // Actions
            const actions = document.createElement('div');
            actions.className = 'message-actions';

            // Always allow reply
            const replyBtn = document.createElement('button');
            replyBtn.className = 'msg-action-btn';
            const replyImg = document.createElement('img');
            replyImg.src = 'assets/img/reply.svg';
            replyImg.alt = '返信';
            replyImg.style.width = '16px';
            replyImg.style.height = '16px';
            replyBtn.appendChild(replyImg);
            replyBtn.title = '返信';
            replyBtn.onclick = () => startReply(m.id, m.username, m.content);
            actions.appendChild(replyBtn);

            // Pin Button
            const isPinned = !!+m.is_pinned;
            const pinBtn = document.createElement('button');
            pinBtn.className = 'msg-action-btn';
            if (isPinned) {
                pinBtn.textContent = '📍';
            } else {
                const pinImg = document.createElement('img');
                pinImg.src = 'assets/img/pin.svg';
                pinImg.alt = 'ピン';
                pinImg.style.width = '16px';
                pinImg.style.height = '16px';
                pinImg.style.opacity = '0.6';
                pinBtn.appendChild(pinImg);
            }
            pinBtn.title = isPinned ? 'ピン解除' : 'ピン留め';
            pinBtn.onclick = () => togglePin(m.id);
            actions.appendChild(pinBtn);

            // Reaction Button
            const reactBtn = document.createElement('button');
            reactBtn.className = 'msg-action-btn';
            const reactImg = document.createElement('img');
            reactImg.src = 'assets/img/emoji.svg';
            reactImg.alt = 'リアクション';
            reactImg.style.width = '16px';
            reactImg.style.height = '16px';
            reactImg.style.opacity = '0.6';
            reactBtn.appendChild(reactImg);
            reactBtn.title = 'リアクション';
            reactBtn.onclick = (e) => showEmojiPicker(e, m.id);
            actions.appendChild(reactBtn);

            // Add Delete/Edit buttons only if owner
            if (m.username === currentUserName) {
                const editBtn = document.createElement('button');
                editBtn.className = 'msg-action-btn';
                const editImg = document.createElement('img');
                editImg.src = 'assets/img/edit.svg';
                editImg.alt = '編集';
                editImg.style.width = '16px';
                editImg.style.height = '16px';
                editBtn.appendChild(editImg);
                editBtn.title = '編集';
                editBtn.onclick = () => startEditMessage(m, false);
                actions.appendChild(editBtn);

                const delBtn = document.createElement('button');
                delBtn.className = 'msg-action-btn';
                delBtn.textContent = '';
                const delImg = document.createElement('img');
                delImg.src = 'assets/img/trash.svg';
                delImg.alt = '削除';
                delImg.style.width = '16px';
                delImg.style.height = '16px';
                delBtn.appendChild(delImg);
                delBtn.title = '削除';
                delBtn.onclick = () => deleteMessage(m.id);
                actions.appendChild(delBtn);
            }

            header.appendChild(user);
            header.appendChild(time);
            header.appendChild(actions);

            // If it's a reply but NOT the direct child in visual tree (redundant check but safe)
            // Or just always show who it's replying to if it's not a root message
            if (m.reply_to_id && m.reply_username) {
                const quote = document.createElement('div');
                quote.className = 'reply-quote';
                quote.style.cursor = 'pointer';
                const replyPrefix = document.createElement('span');
                replyPrefix.style.opacity = '0.6';
                replyPrefix.style.fontSize = '0.8rem';
                replyPrefix.textContent = '↩️ 返信先: ';
                quote.appendChild(replyPrefix);

                const replyUser = document.createElement('strong');
                replyUser.textContent = m.reply_username;
                quote.appendChild(replyUser);
                quote.onclick = () => {
                    const target = document.getElementById('message-' + m.reply_to_id);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        target.style.backgroundColor = 'rgba(99, 102, 241, 0.2)';
                        setTimeout(() => target.style.backgroundColor = '', 2000);
                    }
                };
                info.appendChild(quote);
            }

            // Content
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';

            // Highlight Mentions (@username)
            appendMentionHighlightedText(contentDiv, m.content || '');

            if (m.is_edited == 1) {
                const editedLabel = document.createElement('span');
                editedLabel.style.fontSize = '0.7rem';
                editedLabel.style.opacity = '0.5';
                editedLabel.style.marginLeft = '5px';
                editedLabel.innerText = '(編集済み)';
                contentDiv.appendChild(editedLabel);
            }

            if (m.attachment_path) {
                const ext = m.attachment_path.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
                const isAudio = ['mp3', 'wav', 'ogg'].includes(ext);
                const isVideo = ['mp4', 'webm', 'ogv', 'mov', 'avi'].includes(ext);

                if (isImage) {
                    const img = document.createElement('img');
                    img.src = m.attachment_path;
                    img.className = 'preview-img';
                    img.style.display = 'block';
                    img.style.marginTop = '10px';
                    img.onclick = () => window.open(m.attachment_path, '_blank');
                    contentDiv.appendChild(img);
                } else if (isAudio) {
                    const audio = document.createElement('audio');
                    audio.src = m.attachment_path;
                    audio.controls = true;
                    audio.style.display = 'block';
                    audio.style.marginTop = '10px';
                    audio.style.maxWidth = '100%';
                    contentDiv.appendChild(audio);
                } else if (isVideo) {
                    const video = document.createElement('video');
                    video.src = m.attachment_path;
                    video.controls = true;
                    video.style.display = 'block';
                    video.style.marginTop = '10px';
                    video.style.maxWidth = '100%';
                    contentDiv.appendChild(video);
                }

                const dlLink = document.createElement('a');
                const fileName = m.attachment_path.split('/').pop();
                dlLink.href = 'download.php?file=' + fileName;
                dlLink.target = '_blank';
                dlLink.innerText = '⬇️ ダウンロード';
                dlLink.style.display = 'inline-block';
                dlLink.style.fontSize = '0.75rem';
                dlLink.style.marginTop = '5px';
                dlLink.style.color = 'var(--accent-color)';
                contentDiv.appendChild(dlLink);
            }

            info.appendChild(header);

            // Pinned Badge
            if (!!+m.is_pinned) {
                const pinBadge = document.createElement('div');
                pinBadge.className = 'message-pinned-badge';
                pinBadge.textContent = '📌 ピン留めされたメッセージ';
                info.appendChild(pinBadge);
                group.classList.add('message-pinned');
            }

            info.appendChild(contentDiv);

            // Reactions Display
            if (m.reactions && m.reactions.length > 0) {
                const reactContainer = document.createElement('div');
                reactContainer.className = 'reactions-container';

                // Group by emoji
                const grouped = {};
                m.reactions.forEach(r => {
                    if (!grouped[r.emoji]) grouped[r.emoji] = [];
                    grouped[r.emoji].push(r.user_id);
                });

                Object.keys(grouped).forEach(emoji => {
                    const badge = document.createElement('div');
                    const isMyReaction = grouped[emoji].includes(currentUserId);
                    badge.className = `reaction-badge ${isMyReaction ? 'active' : ''}`;
                    const emojiSpan = document.createElement('span');
                    emojiSpan.textContent = emoji;
                    badge.appendChild(emojiSpan);

                    const countSpan = document.createElement('span');
                    countSpan.className = 'reaction-count';
                    countSpan.textContent = grouped[emoji].length;
                    badge.appendChild(countSpan);
                    badge.onclick = () => toggleReaction(m.id, emoji);
                    reactContainer.appendChild(badge);
                });
                info.appendChild(reactContainer);
            }

            group.appendChild(info);

            wrapper.appendChild(group);

            // Children Container
            if (m.children.length > 0) {
                const childrenDiv = document.createElement('div');
                childrenDiv.className = 'message-children';
                childrenDiv.style.marginLeft = '20px'; // Indent
                childrenDiv.style.marginTop = '8px';
                childrenDiv.style.paddingLeft = '10px';
                childrenDiv.style.borderLeft = '2px solid var(--border-color)';

                m.children.forEach(child => renderMessageNode(child, childrenDiv));
                wrapper.appendChild(childrenDiv);
            }

            parentContainer.appendChild(wrapper);
        }

        function handleInputKey(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
            // Auto-resize textarea
            const el = e.target;
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight) + 'px';
            if (el.value === '') el.style.height = 'auto';
        }

        async function sendMessage() {
            const content = msgInput.value.trim();

            if (!content && !fileToUpload) {
                return;
            }

            const timer = document.getElementById('self-destruct-timer');
            const expiresSec = timer ? timer.value : 0;

            const body = new FormData();
            if (isGroupMode) {
                body.append('group_thread_id', currentGroupThreadId);
            } else {
                body.append('thread_id', currentThreadId);
            }
            body.append('content', content);
            body.append('csrf_token', csrfToken);
            if (replyToId) body.append('reply_to_id', replyToId);
            if (expiresSec > 0) body.append('expires_in', expiresSec);
            if (fileToUpload) body.append('attachment', fileToUpload);

            const result = await api('send_message', 'POST', body);

            if (result.error) {
                alert('メッセージの送信に失敗しました: ' + result.error + (result.details ? '\n' + result.details : ''));
                return;
            }

            // Clear UI
            msgInput.value = '';
            msgInput.style.height = 'auto';
            cancelReply();
            cancelUpload();

            if (isGroupMode) await loadGroupMessages();
            else await loadMessages();
        }

        async function deleteMessage(id) {
            if (!confirm('本当にこのメッセージを削除しますか？')) return;
            const body = new FormData();
            body.append('message_id', id);
            await api('delete_message', 'POST', body);
            if (isGroupMode) loadGroupMessages();
            else loadMessages();
        }

        // --- Reply Logic ---
        function startReply(id, username, content = '') {
            replyToId = id;
            document.getElementById('reply-target-name').innerText = username;
            const preview = document.getElementById('reply-preview-text');
            if (preview) {
                preview.innerText = content.substring(0, 50) + (content.length > 50 ? '...' : '');
            }
            replyBar.classList.add('active');
            msgInput.focus();
        }

        function cancelReply() {
            replyToId = null;
            replyBar.classList.remove('active');
        }

        // --- Drag & Drop Logic ---
        const chatArea = document.querySelector('.chat-area');
        const dropOverlay = document.querySelector('.drag-overlay');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            chatArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        chatArea.addEventListener('dragenter', () => chatArea.classList.add('drag-active'));
        chatArea.addEventListener('dragleave', (e) => {
            if (e.target === dropOverlay) chatArea.classList.remove('drag-active');
        });

        chatArea.addEventListener('drop', (e) => {
            chatArea.classList.remove('drag-active');
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) handleMediaUploadFiles(files); // Changed to handleMediaUploadFiles
        });

        let modalFileToUpload = null;

        function openMediaUploadModal() {
            modalFileToUpload = null;
            const fileInput = document.getElementById('modal-file-input');
            const contentInput = document.getElementById('modal-content-input');
            if (fileInput) fileInput.value = '';
            if (contentInput) contentInput.value = '';

            const previewContainer = document.getElementById('media-upload-preview-container');
            if (previewContainer) {
                previewContainer.textContent = '';
                const placeholder = document.createElement('div');
                placeholder.className = 'upload-placeholder';
                const placeholderIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                placeholderIcon.setAttribute('width', '48');
                placeholderIcon.setAttribute('height', '48');
                placeholderIcon.setAttribute('viewBox', '0 0 24 24');
                placeholderIcon.setAttribute('fill', 'none');
                placeholderIcon.setAttribute('stroke', 'currentColor');
                placeholderIcon.setAttribute('stroke-width', '2');
                placeholderIcon.setAttribute('stroke-linecap', 'round');
                placeholderIcon.setAttribute('stroke-linejoin', 'round');
                placeholderIcon.style.color = 'var(--text-secondary)';
                placeholderIcon.style.marginBottom = '15px';

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4');
                const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
                polyline.setAttribute('points', '17 8 12 3 7 8');
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', '12');
                line.setAttribute('y1', '3');
                line.setAttribute('x2', '12');
                line.setAttribute('y2', '15');

                placeholderIcon.appendChild(path);
                placeholderIcon.appendChild(polyline);
                placeholderIcon.appendChild(line);

                const placeholderText = document.createElement('p');
                placeholderText.style.margin = '0';
                placeholderText.style.color = 'var(--text-secondary)';
                placeholderText.textContent = 'クリックまたはドラッグ＆ドロップで選択';

                placeholder.appendChild(placeholderIcon);
                placeholder.appendChild(placeholderText);
                previewContainer.appendChild(placeholder);
            }

            const modal = document.getElementById('media-upload-modal');
            if (modal) modal.showModal();
        }

        function closeMediaUploadModal() {
            document.getElementById('media-upload-modal').close();
            modalFileToUpload = null;
        }

        function handleMediaUploadFiles(files) {
            if (files.length === 0) return;
            modalFileToUpload = files[0];
            const container = document.getElementById('media-upload-preview-container');
            container.textContent = '';

            if (modalFileToUpload.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.readAsDataURL(modalFileToUpload);
                reader.onloadend = () => {
                    const img = document.createElement('img');
                    img.src = reader.result;
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '300px';
                    img.style.borderRadius = '8px';
                    img.style.objectFit = 'contain';
                    container.appendChild(img);
                }
            } else if (modalFileToUpload.type.startsWith('audio/')) {
                const div = document.createElement('div');
                div.className = 'media-file-info';
                div.style.textAlign = 'center';
                div.style.padding = '20px';

                const icon = document.createElement('span');
                icon.style.fontSize = '3rem';
                icon.textContent = '🎵';
                div.appendChild(icon);

                const name = document.createElement('p');
                name.style.marginTop = '10px';
                name.textContent = modalFileToUpload.name;
                div.appendChild(name);

                container.appendChild(div);
            } else if (modalFileToUpload.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(modalFileToUpload);
                video.style.maxWidth = '100%';
                video.style.maxHeight = '300px';
                video.style.borderRadius = '8px';
                video.muted = true;
                video.autoplay = true;
                video.loop = true;
                container.appendChild(video);
            } else {
                const div = document.createElement('div');
                div.className = 'media-file-info';
                div.style.textAlign = 'center';
                div.style.padding = '20px';

                const icon = document.createElement('span');
                icon.style.fontSize = '3rem';
                icon.textContent = '📄';
                div.appendChild(icon);

                const name = document.createElement('p');
                name.style.marginTop = '10px';
                name.textContent = modalFileToUpload.name;
                div.appendChild(name);

                container.appendChild(div);
            }
        }

        async function submitMediaUpload() {
            if (!modalFileToUpload) {
                alert('ファイルを選択してください');
                return;
            }

            const content = document.getElementById('modal-content-input').value.trim();
            const body = new FormData();
            body.append('content', content);
            body.append('attachment', modalFileToUpload);

            let result;
            if (isDmMode) {
                if (!currentPartnerId) return;
                body.append('receiver_id', currentPartnerId);
                result = await api('send_direct_message', 'POST', body);
            } else {
                if (!currentThreadId) return;
                body.append('thread_id', currentThreadId);
                if (replyToId) body.append('reply_to_id', replyToId);
                result = await api('send_message', 'POST', body);
            }

            if (result.error) {
                alert('送信に失敗しました: ' + result.error);
            } else {
                closeMediaUploadModal();
                if (isDmMode) {
                    await loadDms();
                    await loadDmPartners();
                } else {
                    await loadMessages();
                    cancelReply();
                }
            }
        }

        // Drag and drop for modal
        document.addEventListener('DOMContentLoaded', () => {
            const dropzone = document.getElementById('media-upload-dropzone');
            if (dropzone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });
                dropzone.addEventListener('dragover', () => dropzone.classList.add('drag-active'));
                dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-active'));
                dropzone.addEventListener('drop', (e) => {
                    dropzone.classList.remove('drag-active');
                    handleMediaUploadFiles(e.dataTransfer.files);
                });
            }
        });

        function cancelUpload() {
            fileToUpload = null;
            uploadPreview.classList.remove('active');
            previewContent.textContent = ''; // Clear safely
        }

        async function createThread() {
            const input = document.getElementById('new-thread-name');
            const catInput = document.getElementById('new-thread-category');
            const name = input.value.trim();
            const category = catInput ? catInput.value.trim() : 'General';

            if (!name) return;
            const body = new FormData();
            body.append('name', name);
            body.append('category', category || 'General');
            const result = await api('create_thread', 'POST', body);

            if (result.error) {
                alert('スレッドの作成に失敗しました: ' + result.error);
                return;
            }

            if (catInput) catInput.value = '';
            input.value = '';
            await loadThreads();
            hideCreateThread();

            // Switch to the newly created thread
            if (result.id) {
                switchThread(result.id, name, currentUserId, null, category || 'General');
            }
        }

        function toggleThreadBrowser() {
            const browser = document.getElementById('thread-browser');
            browser.classList.toggle('active');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        }

        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('main-sidebar');
            sidebar.classList.toggle('collapsed');

            // オプション: 折りたたみ状態をLocalStorage等に保存することも検討可能
        }

        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                const tabId = item.getAttribute('data-tab');
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                document.querySelectorAll('.content-pane').forEach(p => {
                    p.classList.remove('active')
                    p.style.display = 'none'; // Ensure hide
                });
                const target = document.getElementById(tabId + '-pane');
                target.classList.add('active');
                target.style.display = 'flex'; // Use Flex for layouts

                if (tabId === 'dm') {
                    isDmMode = true;
                    document.getElementById('thread-browser').classList.remove('active'); // CSS based toggle
                    backToHub();
                } else if (tabId === 'threads') {
                    isDmMode = false;
                    document.getElementById('thread-browser').classList.add('active'); // CSS based toggle
                } else if (tabId === 'favorites') {
                    isDmMode = false;
                    loadFavorites();
                } else if (tabId === 'tactical-map') {
                    isDmMode = false;
                    initTacticalMap();
                }

                // モバイル表示でサイドバーが開いている場合は閉じる
                const sidebar = document.getElementById('main-sidebar');
                if (sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });
        });

        // --- Favorites Logic ---
        async function toggleFavorite() {
            const body = new FormData();
            body.append('thread_id', currentThreadId);
            const res = await api('toggle_favorite', 'POST', body);
            if (res.success) {
                updateFavoriteIcon(res.status === 'added');
                if (document.querySelector('.nav-item[data-tab="favorites"]').classList.contains('active')) {
                    loadFavorites();
                }
            }
        }

        async function checkFavoriteStatus() {
            const res = await api(`
            check_favorite & thread_id = $ {
                currentThreadId
            }
            `);
            updateFavoriteIcon(res.is_favorite);
        }

        function updateFavoriteIcon(isFav) {
            const btn = document.getElementById('fav-btn');
            if (isFav) {
                btn.innerText = '★';
                btn.style.color = 'gold';
            } else {
                btn.innerText = '☆';
                btn.style.color = 'var(--text-secondary)';
            }
        }

        function setTheme(mode) {
            if (mode === 'light') {
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.remove('light-theme');
            }
        }

        function updateAccentColor(color) {
            document.documentElement.style.setProperty('--accent-color', color);
            // Also update hover color (lighter version)
            const r = parseInt(color.slice(1, 3), 16);
            const g = parseInt(color.slice(3, 5), 16);
            const b = parseInt(color.slice(5, 7), 16);
            const hoverColor = `
            rgba($ {
                r
            }, $ {
                g
            }, $ {
                b
            }, 0.8)`;
            document.documentElement.style.setProperty('--accent-hover', hoverColor);
        }

        async function loadFavorites() {
            const threads = await api('get_favorites');
            const list = document.getElementById('fav-thread-list');
            list.innerText = '';
            if (threads.length === 0) {
                const d = document.createElement('div');
                d.style.padding = '1rem';
                d.style.color = 'var(--text-secondary)';
                d.style.fontSize = '0.85rem';
                d.innerText = 'お気に入りスレッドがありません。\nスレッドタイトルの☆を押して追加できます。';
                list.appendChild(d);
                return;
            }
            threads.forEach(t => {
                const item = document.createElement('div');
                item.className = `
            thread - item $ {
                t.id == currentThreadId ? 'active' : ''
            }
            `;
                item.textContent = '# ' + t.name;
                item.onclick = () => {
                    // Switch to Threads tab context implicitly but keep view? 
                    // Better UX: Switch to Threads tab and load this thread.
                    document.querySelector('.nav-item[data-tab="threads"]').click();
                    switchThread(t.id, t.name, t.creator_id);
                };
                list.appendChild(item);
            });
        }

        // --- Discord-like Friend & DM Logic ---

        function backToHub() {
            currentPartnerId = null;
            const hub = document.getElementById('dm-hub-view');
            const chat = document.getElementById('dm-chat-view');
            if (hub && chat) {
                hub.style.display = 'flex';
                chat.style.display = 'none';
                loadHubFriends();
            }
        }

        function switchToDmChat(id, name, avatarUrl = null, status = 'online') {
            currentPartnerId = id;
            document.getElementById('dm-hub-view').style.display = 'none';
            document.getElementById('dm-chat-view').style.display = 'flex';

            const infoContainer = document.getElementById('current-dm-partner-info');
            infoContainer.textContent = '';
            infoContainer.style.display = 'flex';
            infoContainer.style.alignItems = 'center';
            infoContainer.style.gap = '10px';

            infoContainer.appendChild(getAvatarElement(name, status, avatarUrl));
            const nameH3 = document.createElement('h3');
            nameH3.className = 'thread-name';
            nameH3.id = 'current-dm-partner-name';
            nameH3.innerText = name;
            infoContainer.appendChild(nameH3);

            const container = document.getElementById('dm-message-container');
            container.innerText = '';
            container.appendChild(getSkeletonLoader());
            isDmMode = true;
            isGroupMode = false;
            currentGroupThreadId = null;
            loadDms(1000);
            updateMuteIcon();
        }

        async function loadHubFriends() {
            const friends = await api('get_friends');
            const list = document.getElementById('hub-friend-list');
            list.textContent = '';
            if (friends.length === 0) {
                const emptyMsg = document.createElement('div');
                emptyMsg.style.padding = '10px';
                emptyMsg.style.color = 'gray';
                emptyMsg.textContent = 'まだフレンドがいません';
                list.appendChild(emptyMsg);
                return;
            }
            friends.forEach(f => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                d.style.alignItems = 'center';
                d.style.cursor = 'pointer';

                const leftSide = document.createElement('div');
                leftSide.style.display = 'flex';
                leftSide.style.alignItems = 'center';
                leftSide.style.gap = '10px';
                leftSide.appendChild(getAvatarElement(f.username, f.status || 'offline', f.avatar_url));

                const nameSpan = document.createElement('span');
                nameSpan.textContent = f.username;
                leftSide.appendChild(nameSpan);
                d.appendChild(leftSide);

                const timeSpan = document.createElement('span');
                timeSpan.style.fontSize = '0.8rem';
                timeSpan.style.color = 'var(--text-secondary)';
                timeSpan.textContent = f.last_msg_at ? new Date(f.last_msg_at).toLocaleString() : '会話なし';
                d.appendChild(timeSpan);

                d.onclick = () => switchToDmChat(f.id, f.username, f.avatar_url, f.status);
                list.appendChild(d);
            });
        }

        // --- Modal Logic ---
        function showAddFriendModal() {
            document.getElementById('add-friend-modal').showModal();
            document.getElementById('user-search-results').textContent = '';
            document.getElementById('user-search-input').value = '';
        }

        async function searchUsers() {
            const q = document.getElementById('user-search-input').value;
            if (!q) return;
            const res = await api(`
            search_users & q = $ {
                encodeURIComponent(q)
            }
            `);
            const list = document.getElementById('user-search-results');
            list.textContent = '';
            if (res.length === 0) {
                list.innerText = '見つかりませんでした';
                return;
            }
            res.forEach(u => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                d.style.alignItems = 'center';
                d.style.gap = '10px';

                const userPart = document.createElement('div');
                userPart.style.display = 'flex';
                userPart.style.alignItems = 'center';
                userPart.style.gap = '10px';
                userPart.appendChild(getAvatarElement(u.username, u.status, u.avatar_url));
                const nameSpan = document.createElement('span');
                nameSpan.textContent = `
            $ {
                u.username
            }(ID: $ {
                u.id
            })`;
                userPart.appendChild(nameSpan);
                d.appendChild(userPart);

                const btn = document.createElement('button');
                btn.innerText = '申請';
                btn.className = 'btn-primary';
                btn.style.padding = '10px 15px';
                btn.style.fontSize = '1.0rem';
                btn.onclick = async () => {
                    if (confirm(`
            ID: $ {
                u.id
            }
            $ {
                u.username
            }
            に申請を送りますか`)) {
                        const body = new FormData();
                        body.append('target_id', u.id);
                        const r = await api('request_friend', 'POST', body);
                        if (r.success) alert('送信しました');
                        else alert(r.error);
                    }
                };
                d.appendChild(btn);
                list.appendChild(d);
            });
        }

        function showPendingRequestsModal() {
            document.getElementById('pending-requests-modal').showModal();
            loadPendingRequests();
        }

        async function loadPendingRequests() {
            const reqs = await api('get_friend_requests');
            const list = document.getElementById('pending-requests-list-modal');
            list.textContent = '';
            if (reqs.length === 0) list.innerText = '承認待ちのリクエストはありません';
            reqs.forEach(r => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                const nameSpan = document.createElement('span');
                nameSpan.textContent = r.username;
                d.appendChild(nameSpan);
                const btn = document.createElement('button');
                btn.innerText = '承認';
                btn.className = 'btn-primary';
                btn.onclick = async () => {
                    const body = new FormData();
                    body.append('request_id', r.id);
                    await api('accept_friend', 'POST', body);
                    loadPendingRequests();
                    loadHubFriends();
                };
                d.appendChild(btn);
                list.appendChild(d);
            });
        }



        function showBlockedModal() {
            document.getElementById('blocked-users-modal').showModal();
            loadBlockedUsers();
        }

        async function loadBlockedUsers() {
            const users = await api('get_blocked_users');
            const list = document.getElementById('blocked-users-list');
            list.textContent = '';
            if (users.length === 0) list.innerText = 'ブロックしているユーザーはいません';
            users.forEach(u => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                const nameSpan = document.createElement('span');
                nameSpan.textContent = u.username;
                d.appendChild(nameSpan);
                const btn = document.createElement('button');
                btn.innerText = '解除';
                btn.className = 'btn-secondary';
                btn.onclick = async () => {
                    const body = new FormData();
                    body.append('target_id', u.id);
                    await api('unblock_user', 'POST', body);
                    loadBlockedUsers();
                };
                d.appendChild(btn);
                list.appendChild(d);
            });
        }

        async function blockCurrentPartner() {
            if (!currentPartnerId) return;
            if (confirm('このユーザーをブロックしますか？\nフレンドも解除されます。')) {
                const body = new FormData();
                body.append('target_id', currentPartnerId);
                await api('block_user', 'POST', body);
                backToHub();
            }
        }

        // Fallback for partner-list references (if any left) can be ignored as we utilize hub-friend-list
        async function loadDmPartners() {
            // Alias to hub loader if called from polling
            loadHubFriends();
        }

        async function loadDms(minDelay = 0) {
            if (!currentPartnerId) return;
            const startTime = Date.now();
            const dms = await api(`
            get_direct_messages & partner_id = $ {
                currentPartnerId
            }
            `);

            if (minDelay > 0) {
                const elapsed = Date.now() - startTime;
                const remaining = minDelay - elapsed;
                if (remaining > 0) await new Promise(r => setTimeout(r, remaining));
            }

            // Mark as read
            const markBody = new FormData();
            markBody.append('partner_id', currentPartnerId);
            api('mark_dms_as_read', 'POST', markBody);

            const container = document.getElementById('dm-message-container');
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
            container.innerText = '';

            dms.forEach(m => {
                const group = document.createElement('div');
                group.className = 'message-group';
                group.appendChild(getAvatarElement(m.username, 'online', m.avatar_url));

                const info = document.createElement('div');
                info.className = 'message-info';

                const header = document.createElement('div');
                header.className = 'message-header';

                const user = document.createElement('span');
                user.className = 'message-user clickable-username';
                user.textContent = m.username;
                user.style.cursor = 'pointer';
                user.onclick = (e) => {
                    e.stopPropagation();
                    showUserProfile(m.sender_id, m.username);
                };

                const time = document.createElement('span');
                time.className = 'message-time';
                time.textContent = m.created_at;

                if (m.username === currentUserName && m.is_read == 1) {
                    const readLabel = document.createElement('span');
                    readLabel.style.fontSize = '0.7rem';
                    readLabel.style.color = 'var(--accent-color)';
                    readLabel.style.marginLeft = '8px';
                    readLabel.innerText = '既読';
                    time.appendChild(readLabel);
                }

                header.appendChild(user);
                header.appendChild(time);

                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';

                appendMentionHighlightedText(contentDiv, m.content || '');

                if (m.attachment_path) {
                    const ext = m.attachment_path.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
                    const isAudio = ['mp3', 'wav', 'ogg'].includes(ext);
                    const isVideo = ['mp4', 'webm', 'ogv', 'mov', 'avi'].includes(ext);

                    if (isImage) {
                        const img = document.createElement('img');
                        img.src = m.attachment_path;
                        img.className = 'preview-img';
                        img.style.display = 'block';
                        img.style.marginTop = '10px';
                        img.onclick = () => window.open(m.attachment_path, '_blank');
                        contentDiv.appendChild(img);
                    } else if (isAudio) {
                        const audio = document.createElement('audio');
                        audio.src = m.attachment_path;
                        audio.controls = true;
                        audio.style.display = 'block';
                        audio.style.marginTop = '10px';
                        audio.style.maxWidth = '100%';
                        contentDiv.appendChild(audio);
                    } else if (isVideo) {
                        const video = document.createElement('video');
                        video.src = m.attachment_path;
                        video.controls = true;
                        video.style.display = 'block';
                        video.style.marginTop = '10px';
                        video.style.maxWidth = '100%';
                        contentDiv.appendChild(video);
                    }

                    const dlLink = document.createElement('a');
                    const fileName = m.attachment_path.split('/').pop();
                    dlLink.href = 'download.php?file=' + fileName;
                    dlLink.target = '_blank';
                    dlLink.innerText = '⬇️ ダウンロード';
                    dlLink.style.display = 'inline-block';
                    dlLink.style.fontSize = '0.75rem';
                    dlLink.style.marginTop = '5px';
                    dlLink.style.color = 'var(--accent-color)';
                    contentDiv.appendChild(dlLink);
                }

                info.appendChild(header);
                info.appendChild(contentDiv);

                if (m.is_edited == 1) {
                    const editedLabel = document.createElement('span');
                    editedLabel.style.fontSize = '0.7rem';
                    editedLabel.style.opacity = '0.5';
                    editedLabel.style.marginLeft = '5px';
                    editedLabel.innerText = '(編集済み)';
                    contentDiv.appendChild(editedLabel);
                }

                if (m.sender_id == currentUserId) {
                    const editBtn = document.createElement('button');
                    editBtn.className = 'msg-action-btn';
                    editBtn.style.position = 'absolute';
                    editBtn.style.right = '10px';
                    editBtn.style.top = '10px';
                    editBtn.textContent = '';
                    const editImg = document.createElement('img');
                    editImg.src = 'assets/img/edit.svg';
                    editImg.alt = '編集';
                    editImg.style.width = '16px';
                    editImg.style.height = '16px';
                    editBtn.appendChild(editImg);
                    editBtn.onclick = () => startEditMessage(m, true);
                    group.appendChild(editBtn);
                }

                group.appendChild(info);
                container.appendChild(group);
            });

            if (dms.length > 0) {
                const latest = dms[dms.length - 1];
                if (lastDmId !== 0 && latest.id > lastDmId && latest.sender_id != currentUserId) {
                    sendNotification(`新着DM: ${latest.username}`, latest.content, 'dm', currentPartnerId);
                }
                lastDmId = latest.id;
            }

            if (isAtBottom) container.scrollTop = container.scrollHeight;
        }

        async function showUserPicker() {
            const modal = document.getElementById('user-picker-modal');
            const list = document.getElementById('all-user-list');
            list.innerText = 'Loading...';
            modal.showModal();

            const users = await api('get_all_users');
            list.innerText = '';
            users.forEach(u => {
                const d = document.createElement('div');
                d.style.padding = '8px';
                d.style.cursor = 'pointer';
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.alignItems = 'center';
                d.style.gap = '10px';
                d.appendChild(getAvatarElement(u.username, u.status, u.avatar_url));
                const nameSpan = document.createElement('span');
                nameSpan.innerText = u.username;
                d.appendChild(nameSpan);

                d.onclick = async () => {
                    if (confirm(u.username + ' にフレンドリクエストを送信しますか？')) {
                        const body = new FormData();
                        body.append('target_id', u.id);
                        const res = await api('request_friend', 'POST', body);
                        if (res.success) {
                            alert('送信しました');
                            modal.close();
                        } else {
                            alert(res.error || 'エラーが発生しました');
                        }
                    }
                };
                list.appendChild(d);
            });
        }

        async function sendDm() {
            const input = document.getElementById('dm-msg-input');
            const content = input.value.trim();
            if ((!content && !dmFileToUpload) || !currentPartnerId) return;

            const timer = document.getElementById('dm-self-destruct-timer');
            const expiresSec = timer ? timer.value : 0;

            const body = new FormData();
            body.append('receiver_id', currentPartnerId);
            body.append('content', content);
            if (expiresSec > 0) body.append('expires_in', expiresSec);
            if (dmFileToUpload) body.append('attachment', dmFileToUpload);

            const result = await api('send_direct_message', 'POST', body);

            if (result.error) {
                alert('DMの送信に失敗しました: ' + result.error);
                return;
            }

            input.value = '';
            input.style.height = 'auto';
            cancelDmUpload();
            await loadDms();
            await loadDmPartners(); // Refresh logic to put recent at top if sorted
        }

        function handleDmInputKey(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendDm();
            }
        }

        // Reusing drag drop logic for DM logic (simplified)
        const dmChatArea = document.getElementById('dm-chat-area');
        if (dmChatArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dmChatArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            dmChatArea.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt.files.length > 0) {
                    dmFileToUpload = dt.files[0];
                    const pv = document.getElementById('dm-preview-content');
                    pv.innerText = '📄 ' + dmFileToUpload.name;
                    document.getElementById('dm-upload-preview').classList.add('active');
                }
            });
        }

        function cancelDmUpload() {
            dmFileToUpload = null;
            document.getElementById('dm-upload-preview').classList.remove('active');
        }



        function showCreateThread() {
            document.getElementById('create-thread-area').classList.add('active');
            document.getElementById('create-thread-toggle-container').style.display = 'none';
            document.getElementById('new-thread-name').focus();
        }

        function hideCreateThread() {
            document.getElementById('create-thread-area').classList.remove('active');
            document.getElementById('create-thread-toggle-container').style.display = 'block';
            document.getElementById('new-thread-name').value = '';
        }

        // Realtime with Socket.io
        let socket = null;

        function initRealtime() {
            if (typeof io === 'undefined') return;
            socket = io('http://localhost:3000');

            socket.on('connect', () => {
                console.log('Connected to realtime server');
                socket.emit('register', currentUserId);
                if (currentThreadId) socket.emit('join_thread', currentThreadId);
            });

            socket.on('new_message', (msg) => {
                if (!isGroupMode && !isDmMode && currentThreadId == msg.thread_id) {
                    loadMessages();
                }
            });

            socket.on('new_group_message', (msg) => {
                if (isGroupMode && currentGroupThreadId == msg.group_thread_id) {
                    loadGroupMessages();
                }
            });

            socket.on('new_dm', (msg) => {
                if (isDmMode && currentPartnerId == msg.sender_id) {
                    loadDms();
                } else {
                    // Refresh partner list for notification dot if needed
                    loadDmPartners();
                }
            });

            socket.on('typing_status', (data) => {
                const indicator = document.getElementById(isDmMode ? 'dm-typing-indicator' : 'typing-indicator');
                if (indicator) {
                    if (data.isTyping) {
                        indicator.innerText = `${data.username} が入力中...`;
                        indicator.style.visibility = 'visible';
                    } else {
                        indicator.style.visibility = 'hidden';
                    }
                }
            });
        }

        // Push Notifications
        async function initPush() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                const publicKey = 'BN1pSd_YbB6fni2gJ1jRDrPipOsYQlrSXXA6LusnqUuSIi9KRYOMAAHxR-xTKV-nNjybdxHwHoxn2HeDgN1guh8';
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: publicKey
                });

                // Save to backend
                await fetch('index.php?api=push_subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf_token: document.querySelector('[name=csrf_token]').value,
                        ...subscription.toJSON()
                    })
                });
            }
        }

        async function selectThread(id, name) {
            currentThreadId = id;
            const title = document.getElementById('thread-title');
            if (title) title.innerText = '#' + name;

            const container = document.getElementById('message-container');
            if (container) {
                container.innerText = '';
                container.appendChild(getSkeletonLoader());
            }

            if (socket) {
                socket.emit('join_thread', id);
            }

            await loadMessages(500);
            updateThreadActions();
            api(`set_last_thread&thread_id=${id}`);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Initial Load
            loadThreads();
            loadGroupThreads();
            loadMuteStatuses();
            initRealtime();
            initPush();
            // GPS 位置情報取得の初期化 (インターバルを10秒に広げて負荷軽減)
            if (typeof locationManager !== 'undefined') {
                locationManager.init('gps-status-header', 10000);
            }

            if (isDmMode && currentPartnerId) {
                const container = document.getElementById('dm-message-container');
                if (container) {
                    container.innerText = '';
                    container.appendChild(getSkeletonLoader());
                }
                loadDms(1000);
            } else if (!isDmMode && currentThreadId) {
                const container = document.getElementById('message-container');
                if (container) {
                    container.innerText = '';
                    container.appendChild(getSkeletonLoader());
                }
                loadMessages(1000);
            }
            // Also update thread actions logic initially
            updateThreadActions();

            // Notifications
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }

            // 新機能: オンラインユーザーリストの初期ロードと定期更新 (15秒おき)
            loadOnlineUsers();
            setInterval(loadOnlineUsers, 15000);

            // 新機能: DM未読バッジの初期ロードと定期更新 (10秒おき)
            updateUnreadDmBadge();
            setInterval(updateUnreadDmBadge, 10000);

            // Polling (Reduced/Removed except for status)
            setInterval(() => {
                // We keep status update as it's not strictly real-time message dependent
                // fetchTypingUsers(); // Replaced by Socket.io
            }, 5000);
        });

        async function loadMuteStatuses() {
            const res = await api('get_mute_statuses');
            mutedTargets = new Set(res.map(m => `${m.target_type}:${m.target_id}`));
            updateMuteIcon();
        }

        async function toggleMute() {
            const targetType = isDmMode ? 'dm' : (isGroupMode ? 'group' : 'thread');
            const targetId = isDmMode ? currentPartnerId : (isGroupMode ? currentGroupThreadId : currentThreadId);
            if (!targetId) return;

            const key = `${targetType}:${targetId}`;
            const isCurrentlyMuted = mutedTargets.has(key);
            const res = await api('toggle_mute', 'POST', {
                target_type: targetType,
                target_id: targetId,
                is_muted: isCurrentlyMuted ? '0' : '1'
            });

            if (res.success) {
                if (isCurrentlyMuted) mutedTargets.delete(key);
                else mutedTargets.add(key);
                updateMuteIcon();
            }
        }

        function updateMuteIcon() {
            const btn = document.getElementById('mute-btn');
            if (!btn) return;
            const targetType = isDmMode ? 'dm' : (isGroupMode ? 'group' : 'thread');
            const targetId = isDmMode ? currentPartnerId : (isGroupMode ? currentGroupThreadId : currentThreadId);
            const key = `${targetType}:${targetId}`;

            if (mutedTargets.has(key)) {
                btn.style.color = '#f87171';
                btn.title = 'ミュート中';
            } else {
                btn.style.color = 'var(--text-secondary)';
                btn.title = '通知をミュート';
            }
        }

        function sendNotification(title, body, targetType = 'thread', targetId = 0) {
            const key = `${targetType}:${targetId}`;

            // Check keywords first (Keywords override mute)
            let isKeywordMatch = false;
            if (userKeywords.length > 0) {
                isKeywordMatch = userKeywords.some(k => k && body.includes(k));
            }

            if (mutedTargets.has(key) && !isKeywordMatch) return;

            if (!isWindowFocused && Notification.permission === 'granted') {
                new Notification(title, {
                    body,
                    icon: 'SYCS_favicon.svg'
                });
            }
        }
    </script>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="js/webrtc.js"></script>
    <script src="js/locate.js"></script>
    <script>
        // --- Reactions & Pinning ---
        async function toggleReaction(messageId, emoji) {
            const body = new FormData();
            body.append('message_id', messageId);
            body.append('emoji', emoji);
            const res = await api('toggle_reaction', 'POST', body);
            if (res.error) {
                alert('リアクションに失敗しました');
            } else {
                await loadMessages();
                const picker = document.querySelector('.emoji-picker-popover');
                if (picker) picker.remove();
            }
        }

        function showEmojiPicker(event, messageId) {
            event.stopPropagation();
            const existing = document.querySelector('.emoji-picker-popover');
            if (existing) existing.remove();

            const popover = document.createElement('div');
            popover.className = 'emoji-picker-popover';

            const emojis = ['👍', '❤️', '😂', '😮', '😢', '🔥', '✅', '🚀', '👀', '✨', '💯', '🙏'];
            emojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.className = 'emoji-btn';
                btn.innerText = emoji;
                btn.onclick = () => toggleReaction(messageId, emoji);
                popover.appendChild(btn);
            });

            document.body.appendChild(popover);

            const rect = event.target.getBoundingClientRect();
            popover.style.top = (rect.top + window.scrollY - popover.offsetHeight - 10) + 'px';
            popover.style.left = (rect.left + window.scrollX) + 'px';

            const closePicker = (e) => {
                if (!popover.contains(e.target)) {
                    popover.remove();
                    document.removeEventListener('click', closePicker);
                }
            };
            setTimeout(() => document.addEventListener('click', closePicker), 10);
        }

        async function togglePin(messageId) {
            const body = new FormData();
            body.append('message_id', messageId);
            const res = await api('toggle_pin', 'POST', body);
            if (res.error) {
                alert('ピン留めに失敗しました');
            } else {
                await loadMessages();
            }
        }

        // --- Search Logic ---
        function toggleAdvancedSearch() {
            const panel = document.getElementById('advanced-search-panel');
            if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        async function searchMessages() {
            const queryInput = document.getElementById('search-input');
            const keyword = queryInput ? queryInput.value.trim() : '';

            const hasAttachment = document.getElementById('search-has-attachment').checked ? '1' : '0';
            const dateFrom = document.getElementById('search-date-from').value;
            const dateTo = document.getElementById('search-date-to').value;

            if (!keyword && hasAttachment === '0' && !dateFrom && !dateTo) return;

            let url = `search_messages&keyword=${encodeURIComponent(keyword)}&has_attachment=${hasAttachment}`;
            if (dateFrom) url += `&date_from=${dateFrom}`;
            if (dateTo) url += `&date_to=${dateTo}`;

            if (isDmMode) url += `&partner_id=${currentPartnerId}`;
            else if (isGroupMode) url += `&group_thread_id=${currentGroupThreadId}`;
            else url += `&thread_id=${currentThreadId}`;

            const res = await api(url);
            const list = document.getElementById('search-results-list');
            const overlay = document.getElementById('search-results-overlay');

            list.textContent = '';
            overlay.style.display = 'flex';

            if (res.length === 0) {
                const emptyMsg = document.createElement('div');
                emptyMsg.style.padding = '10px';
                emptyMsg.style.color = 'var(--text-secondary)';
                emptyMsg.textContent = '結果が見つかりませんでした';
                list.appendChild(emptyMsg);
                return;
            }

            res.forEach(m => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                const userDiv = document.createElement('div');
                userDiv.style.cssText = 'font-size:0.75rem; color:var(--accent-color); font-weight:700;';
                userDiv.textContent = m.username || '';

                const bodyDiv = document.createElement('div');
                bodyDiv.style.cssText = 'font-size:0.85rem; margin:4px 0;';
                bodyDiv.textContent = m.content || (m.attachment_path ? '[添付ファイル]' : '');

                const timeDiv = document.createElement('div');
                timeDiv.style.cssText = 'font-size:0.65rem; opacity:0.6;';
                timeDiv.textContent = m.created_at || '';

                div.appendChild(userDiv);
                div.appendChild(bodyDiv);
                div.appendChild(timeDiv);
                div.onclick = () => {
                    const target = document.getElementById('message-' + m.id);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        target.style.backgroundColor = 'rgba(99, 102, 241, 0.2)';
                        setTimeout(() => target.style.backgroundColor = '', 2000);
                        toggleSearch(false);
                    } else {
                        alert('メッセージが現在の読み込み範囲外です');
                    }
                };
                list.appendChild(div);
            });
        }

        function toggleSearch(show) {
            const overlay = document.getElementById('search-results-overlay');
            if (overlay) overlay.style.display = show ? 'flex' : 'none';
        }

        // ========== 新機能: ピン留めメッセージ一覧 ==========
        async function showPinnedMessages() {
            const modal = document.getElementById('pinned-messages-modal');
            const list = document.getElementById('pinned-messages-list');
            list.textContent = '';
            const loadingMsg = document.createElement('div');
            loadingMsg.style.textAlign = 'center';
            loadingMsg.style.color = 'var(--text-secondary)';
            loadingMsg.style.padding = '40px 0';
            loadingMsg.textContent = '読み込み中...';
            list.appendChild(loadingMsg);
            modal.showModal();

            let url = 'get_pinned_messages';
            if (isGroupMode && currentGroupThreadId) url += `&group_thread_id=${currentGroupThreadId}`;
            else if (currentThreadId) url += `&thread_id=${currentThreadId}`;
            else {
                list.textContent = '';
                const selectMsg = document.createElement('div');
                selectMsg.style.textAlign = 'center';
                selectMsg.style.color = 'var(--text-secondary)';
                selectMsg.style.padding = '40px 0';
                selectMsg.textContent = 'スレッドを選択してください';
                list.appendChild(selectMsg);
                return;
                return;
            }

            const msgs = await api(url);
            list.textContent = '';

            if (!msgs || msgs.length === 0) {
                const noMsg = document.createElement('div');
                noMsg.style.textAlign = 'center';
                noMsg.style.color = 'var(--text-secondary)';
                noMsg.style.padding = '40px 0';
                noMsg.textContent = 'ピン留めされたメッセージはありません';
                list.appendChild(noMsg);
                return;
            }

            msgs.forEach(m => {
                const div = document.createElement('div');
                div.style.cssText = 'border:1px solid var(--border-color); border-radius:8px; padding:12px; margin-bottom:10px; background:var(--bg-secondary); cursor:pointer; transition: background 0.15s;';
                div.onmouseenter = () => div.style.background = 'var(--card-bg)';
                div.onmouseleave = () => div.style.background = 'var(--bg-secondary)';

                const header = document.createElement('div');
                header.style.cssText = 'display:flex; align-items:center; gap:8px; margin-bottom:6px;';
                header.appendChild(getAvatarElement(m.username, 'online', m.avatar_url));

                const userSpan = document.createElement('span');
                userSpan.style.cssText = 'font-weight:600; font-size:0.9rem;';
                userSpan.textContent = m.username || '';

                const dateSpan = document.createElement('span');
                dateSpan.style.cssText = 'font-size:0.75rem; color:var(--text-secondary);';
                dateSpan.textContent = m.created_at || '';

                header.appendChild(userSpan);
                header.appendChild(dateSpan);

                const content = document.createElement('div');
                content.style.cssText = 'font-size:0.9rem; color:var(--text-primary); padding-left:4px; white-space:pre-wrap; word-break:break-word;';
                content.innerText = m.content || '[添付ファイル]';

                const actions = document.createElement('div');
                actions.style.cssText = 'display:flex; gap:8px; margin-top:10px;';

                const jumpBtn = document.createElement('button');
                jumpBtn.className = 'btn-secondary';
                jumpBtn.style.cssText = 'padding:4px 12px; font-size:0.8rem;';
                jumpBtn.innerText = '↗️ ジャンプ';
                jumpBtn.onclick = () => {
                    modal.close();
                    const target = document.getElementById('message-' + m.id);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        target.style.backgroundColor = 'rgba(99, 102, 241, 0.25)';
                        setTimeout(() => target.style.backgroundColor = '', 2000);
                    }
                };

                const unpinBtn = document.createElement('button');
                unpinBtn.className = 'btn-secondary';
                unpinBtn.style.cssText = 'padding:4px 12px; font-size:0.8rem; color:#f87171;';
                unpinBtn.innerText = '📌 解除';
                unpinBtn.onclick = async () => {
                    const body = new FormData();
                    body.append('message_id', m.id);
                    await api('toggle_pin', 'POST', body);
                    showPinnedMessages();
                    if (!isDmMode && !isGroupMode) loadMessages();
                };

                actions.appendChild(jumpBtn);
                actions.appendChild(unpinBtn);

                div.appendChild(header);
                div.appendChild(content);
                div.appendChild(actions);
                list.appendChild(div);
            });
        }

        // ========== 新機能: オンラインユーザーリスト ==========
        let onlineUsersCollapsed = false;

        function toggleOnlineUsers() {
            onlineUsersCollapsed = !onlineUsersCollapsed;
            const list = document.getElementById('online-users-list');
            const icon = document.getElementById('online-users-toggle-icon');
            if (list) list.style.display = onlineUsersCollapsed ? 'none' : 'block';
            if (icon) icon.innerText = onlineUsersCollapsed ? '▸' : '▾';
        }

        async function loadOnlineUsers() {
            if (onlineUsersCollapsed) return;
            const list = document.getElementById('online-users-list');
            if (!list) return;

            const users = await api('get_online_users');
            list.textContent = '';

            if (!users || users.length === 0) {
                const noOnline = document.createElement('div');
                noOnline.style.padding = '6px 12px';
                noOnline.style.fontSize = '0.8rem';
                noOnline.style.color = 'var(--text-secondary)';
                noOnline.textContent = 'オンラインユーザーなし';
                list.appendChild(noOnline);
                return;
            }

            const statusLabels = {
                online: '連絡可能',
                busy: '取り込み中',
                not_allowed: '応答不可',
                step_out: '一時退席中',
                going_away: '外出中',
                away: '退席中'
            };

            users.forEach(u => {
                const item = document.createElement('div');
                item.style.cssText = 'display:flex; align-items:center; gap:8px; padding:5px 10px; cursor:pointer; border-radius:4px; transition:background 0.15s;';
                item.onmouseenter = () => item.style.background = 'var(--hover-bg, rgba(255,255,255,0.05))';
                item.onmouseleave = () => item.style.background = 'transparent';

                const avatarEl = getAvatarElement(u.username, u.status || 'online', u.avatar_url);
                avatarEl.style.transform = 'scale(0.8)';
                avatarEl.style.transformOrigin = 'left center';

                const info = document.createElement('div');
                info.style.cssText = 'flex:1; min-width:0;';
                const nameDiv = document.createElement('div');
                nameDiv.style.cssText = 'font-size:0.8rem; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
                nameDiv.textContent = u.username || '';

                const statusDiv = document.createElement('div');
                statusDiv.style.cssText = 'font-size:0.68rem; color:var(--text-secondary);';
                statusDiv.textContent = statusLabels[u.status] || u.status || '';

                info.appendChild(nameDiv);
                info.appendChild(statusDiv);

                item.appendChild(avatarEl);
                item.appendChild(info);
                item.onclick = () => showUserProfile(u.id, u.username);
                list.appendChild(item);
            });
        }

        // ========== 新機能: DM未読バッジ ==========
        async function updateUnreadDmBadge() {
            const res = await api('get_unread_dm_counts');
            if (!res || res.error) return;

            const badge = document.getElementById('dm-unread-badge');
            if (!badge) return;

            if (res.total > 0) {
                badge.style.display = 'inline-block';
                badge.innerText = res.total > 99 ? '99+' : res.total;
            } else {
                badge.style.display = 'none';
                badge.innerText = '';
            }

            // フレンドリストの各アイテムにもバッジを付与
            if (res.counts) {
                Object.entries(res.counts).forEach(([senderId, count]) => {
                    const el = document.getElementById(`hub-friend-unread-${senderId}`);
                    if (el) {
                        el.style.display = count > 0 ? 'inline-block' : 'none';
                        el.innerText = count > 9 ? '9+' : count;
                    }
                });
            }
        }

        // フレンドリストにバッジを付与するためloadHubFriendsを拡張
        const _origLoadHubFriends = loadHubFriends;
        loadHubFriends = async function() {
            await _origLoadHubFriends();
            // バッジ要素を各フレンドアイテムに追加
            const friends = document.querySelectorAll('#hub-friend-list .thread-item');
            // Note: バッジは動的に未読カウント取得後に反映されるため再取得
            await updateUnreadDmBadge();
        };

        // ========== 新機能: キーボードショートカット ==========
        document.addEventListener('keydown', (e) => {
            const focused = document.activeElement;
            const isInputFocused = focused && (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA' || focused.tagName === 'SELECT');

            // Esc: リプライキャンセル / 検索結果を閉じる / モーダルを閉じる
            if (e.key === 'Escape') {
                const overlay = document.getElementById('search-results-overlay');
                if (overlay && overlay.style.display !== 'none') {
                    toggleSearch(false);
                    return;
                }
                if (replyToId) {
                    cancelReply();
                    return;
                }
                // 開いているモーダルを閉じる
                const openDialogs = document.querySelectorAll('dialog[open]');
                openDialogs.forEach(d => d.close());
                return;
            }

            // Alt+? : キーボードショートカット一覧
            if (e.altKey && e.key === '?') {
                e.preventDefault();
                document.getElementById('keyboard-shortcuts-modal').showModal();
                return;
            }

            // Alt+P : ピン留め一覧
            if (e.altKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                showPinnedMessages();
                return;
            }

            // / : 入力フィールドにフォーカスがない場合、検索入力にフォーカス
            if (e.key === '/' && !isInputFocused) {
                e.preventDefault();
                const searchInput = document.getElementById('search-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
                return;
            }
        });

        // --- Typing Indicator ---
        let typingTimeout = null;
        let isTypingSent = false;

        function updateTypingStatus(isTyping) {
            if (isTyping === isTypingSent) return;
            isTypingSent = isTyping;

            const body = new FormData();
            // Use a specific negative ID or prefix for DMs to avoid collision? 
            // Better: use currentThreadId which is 0 or null for DM, but we need to distinguish partners.
            // Let's use partner_id for DMs.
            const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
            body.append('thread_id', targetId);
            body.append('is_typing', isTyping ? '1' : '0');
            api('update_typing_status', 'POST', body);
        }

        function handleTyping() {
            if (socket) {
                const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
                socket.emit('typing', {
                    threadId: targetId,
                    userId: currentUserId,
                    username: currentUserName,
                    isTyping: true
                });
            }
            updateTypingStatus(true);
            if (typingTimeout) clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                if (socket) {
                    const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
                    socket.emit('typing', {
                        threadId: targetId,
                        userId: currentUserId,
                        username: currentUserName,
                        isTyping: false
                    });
                }
                updateTypingStatus(false);
            }, 3000);
        }

        async function fetchTypingUsers() {
            const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
            if (!targetId) return;
            const res = await api(`get_typing_users&thread_id=${targetId}`);
            const indicator = document.getElementById(isDmMode ? 'dm-typing-indicator' : 'typing-indicator');
            if (indicator) {
                if (res.length > 0) {
                    const names = res.map(u => u.username).join(', ');
                    indicator.innerText = `${names} が入力中...`;
                    indicator.style.visibility = 'visible';
                } else {
                    indicator.innerText = '';
                    indicator.style.visibility = 'hidden';
                }
            }
        }

        function startEditMessage(m, isDm) {
            const newContent = prompt('メッセージを編集:', m.content);
            if (newContent !== null && newContent !== m.content) {
                saveEditMessage(m.id, newContent, isDm);
            }
        }

        async function saveEditMessage(id, content, isDm) {
            const body = new FormData();
            if (isDm) body.append('dm_id', id);
            else body.append('message_id', id);
            body.append('content', content);
            const res = await api('edit_message', 'POST', body);
            if (res.success) {
                if (isDm) loadDms();
                else if (isGroupMode) loadGroupMessages();
                else loadMessages();
            } else {
                alert('編集に失敗しました');
            }
        }

        let tacticalMap = null;
        let mapMarkers = {};

        function initTacticalMap() {
            if (tacticalMap) {
                tacticalMap.remove();
                tacticalMap = null;
            }

            // Default to Tokyo if no GPS
            const lat = locationManager.gpsData.lat || 35.6812;
            const lon = locationManager.gpsData.lon || 139.7671;

            tacticalMap = L.map('tac-map-container', {
                zoomControl: false,
                attributionControl: false
            }).setView([lat, lon], 15);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 20
            }).addTo(tacticalMap);

            L.control.zoom({
                position: 'bottomright'
            }).addTo(tacticalMap);

            updateMapMarkers();
            // 既にインターバルが設定されている場合は重複を避ける（本来は一箇所で管理すべきだが）
        }

        async function updateMapMarkers() {
            if (!tacticalMap || !document.getElementById('tactical-map-pane').classList.contains('active')) return;

            const locations = await api('get_user_locations');

            const statusHeader = document.getElementById('gps-status-header');
            if (statusHeader && locationManager.gpsData.lat) {
                statusHeader.innerText = `自機位置: ${locationManager.gpsData.lat.toFixed(4)}, ${locationManager.gpsData.lon.toFixed(4)}`;
            }

            const currentIds = locations.map(l => l.user_id.toString());
            Object.keys(mapMarkers).forEach(id => {
                if (!currentIds.includes(id)) {
                    tacticalMap.removeLayer(mapMarkers[id]);
                    delete mapMarkers[id];
                }
            });

            locations.forEach(loc => {
                const id = loc.user_id;
                const latlon = [loc.lat, loc.lon];
                const isMe = id == currentUserId;

                if (mapMarkers[id]) {
                    mapMarkers[id].setLatLng(latlon);
                } else {
                    const icon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker-pin ${isMe ? 'me' : ''}" style="background-image: url('${loc.avatar_url || 'assets/img/default-avatar.png'}')"></div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    });

                    const marker = L.marker(latlon, {
                        icon: icon
                    }).addTo(tacticalMap);
                    marker.bindPopup(`<strong>${loc.username}</strong><br>精度: ${loc.accuracy}m<br>更新: ${loc.updated_at}`);
                    mapMarkers[id] = marker;
                }
            });
        }

        // 定期更新用のインターバルを設定（一度だけ）
        setInterval(updateMapMarkers, 10000);

        async function showAttachmentGallery() {
            const modal = document.getElementById('gallery-modal');
            const content = document.getElementById('gallery-content');
            content.textContent = '';
            const loading = document.createElement('div');
            loading.style.gridColumn = '1/-1';
            loading.style.textAlign = 'center';
            loading.textContent = '読み込み中...';
            content.appendChild(loading);
            modal.showModal();

            const url = isDmMode ? `get_attachments&partner_id=${currentPartnerId}` : `get_attachments&thread_id=${currentThreadId}`;
            const files = await api(url);

            content.textContent = '';
            if (files.length === 0) {
                const noFiles = document.createElement('div');
                noFiles.style.gridColumn = '1/-1';
                noFiles.style.textAlign = 'center';
                noFiles.style.color = 'var(--text-secondary)';
                noFiles.textContent = '添付ファイルはありません';
                content.appendChild(noFiles);
                return;
            }

            files.forEach(f => {
                const path = f.attachment_path;
                const ext = path.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);

                const item = document.createElement('div');
                item.style.background = 'var(--card-bg)';
                item.style.borderRadius = '8px';
                item.style.overflow = 'hidden';
                item.style.border = '1px solid var(--border-color)';
                item.style.cursor = 'pointer';
                item.onclick = () => window.open(path, '_blank');

                if (isImage) {
                    const img = document.createElement('img');
                    img.src = path;
                    img.style.width = '100%';
                    img.style.height = '120px';
                    img.style.objectFit = 'cover';
                    item.appendChild(img);
                } else {
                    const placeholder = document.createElement('div');
                    placeholder.style.height = '120px';
                    placeholder.style.display = 'flex';
                    placeholder.style.flexDirection = 'column';
                    placeholder.style.justifyContent = 'center';
                    placeholder.style.alignItems = 'center';
                    placeholder.style.fontSize = '2rem';
                    placeholder.textContent = '📄';
                    const nameDiv = document.createElement('div');
                    nameDiv.style.fontSize = '0.7rem';
                    nameDiv.style.marginTop = '8px';
                    nameDiv.style.padding = '0 4px';
                    nameDiv.style.overflow = 'hidden';
                    nameDiv.style.textOverflow = 'ellipsis';
                    nameDiv.style.width = '100%';
                    nameDiv.style.textAlign = 'center';
                    nameDiv.textContent = path.split('/').pop();
                    placeholder.appendChild(nameDiv);
                    item.appendChild(placeholder);
                }
                content.appendChild(item);
            });
        }
    </script>

    <!-- PWA Installation Logic moved to integrated locations -->

    <!-- Offline Indicator -->
    <div id="offline-indicator" style="display:none; position:fixed; top:0; left:0; right:0; background:#ef4444; color:white; text-align:center; padding:6px; font-size:0.8rem; font-family:'Inter',sans-serif; z-index:10001; animation: slideDown 0.3s ease-out;">
        ⚠️ オフラインです - 一部の機能が制限されます
    </div>

    <style>
        @keyframes slideUpBanner {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .pwa-install-banner-integrated {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-25%);
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            font-family: 'Inter', sans-serif;
            display: none;
            align-items: center;
            gap: 16px;
            animation: slideUpBanner 0.5s cubic-bezier(0.18, 0.89, 0.32, 1.28);
            z-index: 10000;
            width: auto;
            max-width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        /* Standalone mode adjustments */
        @media all and (display-mode: standalone) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }
        }
    </style>

    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('./sw.js', {
                        scope: './'
                    });
                    console.log('[PWA] Service Worker 登録成功:', registration.scope);

                    // Check for updates periodically
                    setInterval(() => {
                        registration.update();
                    }, 60 * 60 * 1000); // 1時間ごと
                } catch (error) {
                    console.warn('[PWA] Service Worker 登録失敗:', error);
                }
            });
        }

        // PWA Install Prompt
        let deferredPrompt = null;

        function showPwaInstallBanners(show = true) {
            const bannerThreads = document.getElementById('pwa-install-banner-threads');
            const bannerDm = document.getElementById('pwa-install-banner-dm');
            const display = show ? 'flex' : 'none';
            if (bannerThreads) bannerThreads.style.display = display;
            if (bannerDm) bannerDm.style.display = display;
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            setTimeout(() => showPwaInstallBanners(), 1000);
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('pwa-install-dismissed')) {
                setTimeout(() => showPwaInstallBanners(), 3000);
            }
        });

        async function installPWA() {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const {
                outcome
            } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            showPwaInstallBanners(false);
        }

        function dismissInstallBanner() {
            const bannerThreads = document.getElementById('pwa-install-banner-threads');
            const bannerDm = document.getElementById('pwa-install-banner-dm');
            if (bannerThreads) bannerThreads.style.display = 'none';
            if (bannerDm) bannerDm.style.display = 'none';
            localStorage.setItem('pwa-install-dismissed', Date.now());
        }

        // Online/Offline detection
        window.addEventListener('online', () => {
            const indicator = document.getElementById('offline-indicator');
            if (indicator) indicator.style.display = 'none';
            console.log('[PWA] オンラインに復帰');
        });

        window.addEventListener('offline', () => {
            const indicator = document.getElementById('offline-indicator');
            if (indicator) indicator.style.display = 'block';
            console.log('[PWA] オフラインになりました');
        });

        // Check initial state
        if (!navigator.onLine) {
            const indicator = document.getElementById('offline-indicator');
            if (indicator) indicator.style.display = 'block';
        }

        // App installed event
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] アプリがインストールされました');
            deferredPrompt = null;
            const bannerThreads = document.getElementById('pwa-install-banner-threads');
            const bannerDm = document.getElementById('pwa-install-banner-dm');
            if (bannerThreads) bannerThreads.style.display = 'none';
            if (bannerDm) bannerDm.style.display = 'none';
        });
    </script>
</body>

</html>
