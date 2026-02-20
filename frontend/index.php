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
function sendDiscordWebhook($webhookUrl, $username, $content, $avatarUrl = null, $attachmentPath = null)
{
    if (!$webhookUrl) return;

    // Use absolute URL for avatar and attachment if they exist
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
    if ($avatarUrl && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
        $avatarUrl = $baseUrl . '/' . ltrim($avatarUrl, '/');
    }

    $fullContent = $content;
    if ($attachmentPath) {
        $absAttachment = $baseUrl . '/' . ltrim($attachmentPath, '/');
        $fullContent .= "\n" . $absAttachment;
    }

    $data = [
        'username' => $username . " (SYCS)",
        'content' => $fullContent,
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
    @file_get_contents($webhookUrl, false, $context);
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
    @file_get_contents($url, false, $context);
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
        @file_get_contents($url, false, $context);
    }
}

// Helper to verify CSRF
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF Token']);
        exit;
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
        verify_csrf();
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
        $stmt = $mysqli->prepare("UPDATE users SET bio = ?, banner_color = ?, status = ?, social_links = ?, theme_preference = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $bio, $bannerColor, $status, $social, $themePref, $userId);
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf(); // Enforce CSRF Check
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        $threadId = $_GET['thread_id'] ?? 0;
        $keyword = $_GET['keyword'] ?? '';
        if ($threadId && $keyword) {
            $query = "%$keyword%";
            $stmt = $mysqli->prepare("
            SELECT m.*, u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.thread_id = ? AND m.content LIKE ? 
            ORDER BY m.created_at DESC 
            LIMIT 50
        ");
            $stmt->bind_param("is", $threadId, $query);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            echo json_encode(['error' => 'Keyword and Thread ID required']);
        }
        exit;
    }

    if ($action === 'update_typing_status') {
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf(); // Enforce CSRF Check
        $threadId = $_POST['thread_id'] ?? 0;
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

        if (($threadId && $content !== '') || ($threadId && $attachmentPath)) {
            $expiresIn = $_POST['expires_in'] ?? 0;
            $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null;

            $stmt = $mysqli->prepare("INSERT INTO messages (thread_id, user_id, content, reply_to_id, attachment_path, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisiss", $threadId, $userId, $content, $replyToId, $attachmentPath, $expiresAt);
            $stmt->execute();
            $msgId = $stmt->insert_id;
            $stmt->close();

            // Notify Realtime Server
            $newMsg = [
                'id' => $msgId,
                'thread_id' => $threadId,
                'user_id' => $userId,
                'content' => $content,
                'attachment_path' => $attachmentPath,
                'username' => $_SESSION['username'] ?? 'User',
                'created_at' => date('Y-m-d H:i:s')
            ];
            notifyRealtimeServer('new_message', ['threadId' => $threadId, 'message' => $newMsg]);

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
                        sendDiscordWebhook($wRow['discord_webhook_url'], $uRow['username'], $content, $uRow['avatar_url'], $attachmentPath);
                    }
                    $uStmt->close();
                }
            }
            $wStmt->close();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Thread and content/attachment required']);
        }
        exit;
    }

    if ($action === 'delete_message') {
        verify_csrf(); // Enforce CSRF Check
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
        verify_csrf();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYCS - Shinjuku Yamabuki Chat System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    <link rel="icon" href="SYCS_favicon.svg" type="image/svg+xml">
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
    </style>
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <aside id="main-sidebar" class="sidebar">
            <div class="sidebar-top">
                <div class="logo-container">
                    <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
                    <span class="logo-version" style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v1.0.4</span>
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
                                <button class="icon-btn" onclick="searchMessages()" style="padding:2px; height:auto; background:transparent;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                            </div>
                            <button class="icon-btn" onclick="startMeeting()" title="ビデオ会議">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="showAttachmentGallery()" title="添付ファイル一覧">
                                <img src="assets/img/files.svg" alt="ギャラリー" style="width:16px; height:16px; filter: grayscale(1) invert(1);">
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
                        <span>スレッド</span>
                        <div class="close-btn" onclick="toggleThreadBrowser()"><svg width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg></div>
                    </div>
                    <div id="thread-list" class="thread-list"></div>
                    <div id="create-thread-area" class="create-thread-area" style="border-top: none;">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span style="font-weight:600; font-size:0.9rem;">新規スレッド</span>
                            <div class="close-btn" onclick="hideCreateThread()">✕</div>
                        </div>
                        <input type="text" id="new-thread-name" class="create-input" placeholder="新スレッド名">
                        <input type="text" id="new-thread-category" class="create-input" placeholder="カテゴリー (任意)" style="margin-top:5px;">
                        <button onclick="createThread()" class="btn-primary" style="padding:0.6rem;">作成</button>
                    </div>
                </aside>
                <!-- Partner Browser for DM -->

                <dialog id="user-picker-modal"
                    style="border:none; border-radius:8px; padding:1rem; background:var(--bg-secondary); color:var(--text-primary);">
                    <h3>ユーザーを選択</h3>
                    <div id="all-user-list" style="max-height:300px; overflow-y:auto; margin:1rem 0;"></div>
                    <button onclick="document.getElementById('user-picker-modal').close()">閉じる</button>
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
                        <button class="btn-secondary" onclick="submitMediaUpload()" style="padding:10px 50px;">送信</button>
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
        let dmFileToUpload = null;
        let isDmMode = false;
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
                div.innerHTML = `<img src="${avatarUrl}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
            } else {
                div.style.background = colors[colorIdx];
                div.innerText = initial;
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
                    console.error('JSON parse error:', parseError);
                    return {
                        error: 'サーバーエラー: JSONパースに失敗しました',
                        details: text.substring(0, 200)
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
                    item.className = `thread-item ${t.id == currentThreadId ? 'active' : ''}`;
                    item.textContent = '# ' + t.name;
                    item.onclick = () => switchThread(t.id, t.name, t.creator_id, t.discord_webhook_url, t.category);
                    list.appendChild(item);
                });
            }
        }

        async function switchThread(id, name, creatorId, webhookUrl = null, category = 'General') {
            currentThreadId = id;
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
            checkFavoriteStatus(); // Check fav status on switch
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
                    container.innerHTML = `<img src="${e.target.result}" class="discord-avatar" id="preview-avatar-img">`;
                    container.innerText = '';
                    document.getElementById('btn-remove-avatar').style.display = 'inline-block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeAvatarPreview() {
            shouldRemoveAvatar = true;
            document.getElementById('edit-avatar-input').value = '';
            const container = document.getElementById('preview-avatar-container');
            container.innerHTML = currentUserName ? currentUserName.charAt(0).toUpperCase() : '?';
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

            const body = new FormData();
            body.append('csrf_token', '<?= $_SESSION["csrf_token"] ?>');
            body.append('bio', bio);
            body.append('banner_color', banner);
            body.append('status', status);
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
                avatarContainer.innerHTML = `<img src="${res.avatar_url}" class="discord-avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
            } else {
                const initial = res.username ? res.username.charAt(0).toUpperCase() : '?';
                avatarContainer.innerHTML = initial;
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
            snsContainer.innerHTML = '';
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
                    sendNotification(`新着メッセージ (#${document.getElementById('current-thread-name').innerText})`, `${latest.username}: ${latest.content}`);
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
            replyBtn.innerHTML = '<img src="assets/img/reply.svg" alt="返信" style="width:16px; height:16px;">';
            replyBtn.title = '返信';
            replyBtn.onclick = () => startReply(m.id, m.username, m.content);
            actions.appendChild(replyBtn);

            // Pin Button
            const isPinned = !!+m.is_pinned;
            const pinBtn = document.createElement('button');
            pinBtn.className = 'msg-action-btn';
            pinBtn.innerHTML = isPinned ? '📍' : '<img src="assets/img/pin.svg" alt="ピン" style="width:16px; height:16px; opacity:0.6;">';
            pinBtn.title = isPinned ? 'ピン解除' : 'ピン留め';
            pinBtn.onclick = () => togglePin(m.id);
            actions.appendChild(pinBtn);

            // Reaction Button
            const reactBtn = document.createElement('button');
            reactBtn.className = 'msg-action-btn';
            reactBtn.innerHTML = '<img src="assets/img/emoji.svg" alt="リアクション" style="width:16px; height:16px; opacity:0.6;">';
            reactBtn.title = 'リアクション';
            reactBtn.onclick = (e) => showEmojiPicker(e, m.id);
            actions.appendChild(reactBtn);

            // Add Delete/Edit buttons only if owner
            if (m.username === currentUserName) {
                const editBtn = document.createElement('button');
                editBtn.className = 'msg-action-btn';
                editBtn.innerHTML = '<img src="assets/img/edit.svg" alt="編集" style="width:16px; height:16px;">';
                editBtn.title = '編集';
                editBtn.onclick = () => startEditMessage(m, false);
                actions.appendChild(editBtn);

                const delBtn = document.createElement('button');
                delBtn.className = 'msg-action-btn';
                delBtn.innerHTML = '<img src="assets/img/trash.svg" alt="削除" style="width:16px; height:16px;">';
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
                quote.innerHTML = `<span style="opacity:0.6; font-size:0.8rem;">↩️ 返信先: </span><strong>${m.reply_username}</strong>`;
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
            let text = m.content || '';
            const mentionRegex = /@([a-zA-Z0-9_]+)/g;
            const highlightedText = text.replace(mentionRegex, (match, username) => {
                if (username === currentUserName) {
                    return `<span class="mention mention-me">${match}</span>`;
                }
                return `<span class="mention">${match}</span>`;
            });
            contentDiv.innerHTML = highlightedText;

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
                pinBadge.innerHTML = '📌 ピン留めされたメッセージ';
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
                    badge.innerHTML = `<span>${emoji}</span><span class="reaction-count">${grouped[emoji].length}</span>`;
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
            body.append('thread_id', currentThreadId);
            body.append('content', content);
            if (replyToId) body.append('reply_to_id', replyToId);
            if (expiresSec > 0) body.append('expires_in', expiresSec);
            if (fileToUpload) body.append('attachment', fileToUpload);

            const result = await api('send_message', 'POST', body);

            if (result.error) {
                alert('メッセージの送信に失敗しました: ' + result.error);
                return;
            }

            // Clear UI
            msgInput.value = '';
            msgInput.style.height = 'auto';
            cancelReply();
            cancelUpload();

            await loadMessages();
        }

        async function deleteMessage(id) {
            if (!confirm('本当にこのメッセージを削除しますか？')) return;
            const body = new FormData();
            body.append('message_id', id);
            await api('delete_message', 'POST', body);
            loadMessages();
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
            document.getElementById('modal-file-input').value = '';
            document.getElementById('modal-content-input').value = '';
            document.getElementById('media-upload-preview-container').innerHTML = `
                <div class="upload-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 15px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p style="margin:0; color:var(--text-secondary);">クリックまたはドラッグ＆ドロップで選択</p>
                </div>
            `;
            document.getElementById('media-upload-modal').showModal();
        }

        function closeMediaUploadModal() {
            document.getElementById('media-upload-modal').close();
            modalFileToUpload = null;
        }

        function handleMediaUploadFiles(files) {
            if (files.length === 0) return;
            modalFileToUpload = files[0];
            const container = document.getElementById('media-upload-preview-container');
            container.innerHTML = '';

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
                div.innerHTML = `<span style="font-size:3rem;">🎵</span><p style="margin-top:10px;">${modalFileToUpload.name}</p>`;
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
                div.innerHTML = `<span style="font-size:3rem;">📄</span><p style="margin-top:10px;">${modalFileToUpload.name}</p>`;
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
            const res = await api(`check_favorite&thread_id=${currentThreadId}`);
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
            const hoverColor = `rgba(${r}, ${g}, ${b}, 0.8)`;
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
                item.className = `thread-item ${t.id == currentThreadId ? 'active' : ''}`;
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
            infoContainer.innerHTML = '';
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
            loadDms(1000);
        }

        async function loadHubFriends() {
            const friends = await api('get_friends');
            const list = document.getElementById('hub-friend-list');
            list.innerHTML = '';
            if (friends.length === 0) {
                list.innerHTML = '<div style="padding:10px; color:gray;">まだフレンドがいません</div>';
                return;
            }
            friends.forEach(f => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                d.style.alignItems = 'center';
                d.style.cursor = 'pointer';
                d.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px;">
                        ${getAvatarElement(f.username, f.status || 'offline', f.avatar_url).outerHTML}
                        <span>${f.username}</span>
                    </div>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">
                        ${f.last_msg_at ? new Date(f.last_msg_at).toLocaleString() : '会話なし'}
                    </span>
                `;
                d.onclick = () => switchToDmChat(f.id, f.username, f.avatar_url, f.status);
                list.appendChild(d);
            });
        }

        // --- Modal Logic ---
        function showAddFriendModal() {
            document.getElementById('add-friend-modal').showModal();
            document.getElementById('user-search-results').innerHTML = '';
            document.getElementById('user-search-input').value = '';
        }

        async function searchUsers() {
            const q = document.getElementById('user-search-input').value;
            if (!q) return;
            const res = await api(`search_users&q=${encodeURIComponent(q)}`);
            const list = document.getElementById('user-search-results');
            list.innerHTML = '';
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
                nameSpan.innerText = `${u.username} (ID:${u.id})`;
                userPart.appendChild(nameSpan);
                d.appendChild(userPart);

                const btn = document.createElement('button');
                btn.innerText = '申請';
                btn.className = 'btn-primary';
                btn.style.padding = '10px 15px';
                btn.style.fontSize = '1.0rem';
                btn.onclick = async () => {
                    if (confirm(`ID:${u.id} ${u.username}に申請を送りますか？`)) {
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
            list.innerHTML = '';
            if (reqs.length === 0) list.innerText = '承認待ちのリクエストはありません';
            reqs.forEach(r => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                d.innerHTML = `<span>${r.username}</span>`;
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
            list.innerHTML = '';
            if (users.length === 0) list.innerText = 'ブロックしているユーザーはいません';
            users.forEach(u => {
                const d = document.createElement('div');
                d.className = 'thread-item';
                d.style.display = 'flex';
                d.style.justifyContent = 'space-between';
                d.innerHTML = `<span>${u.username}</span>`;
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
            const dms = await api(`get_direct_messages&partner_id=${currentPartnerId}`);

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

                let text = m.content || '';
                const mentionRegex = /@([a-zA-Z0-9_]+)/g;
                const highlightedText = text.replace(mentionRegex, (match, username) => {
                    if (username === currentUserName) {
                        return `<span class="mention mention-me">${match}</span>`;
                    }
                    return `<span class="mention">${match}</span>`;
                });
                contentDiv.innerHTML = highlightedText;

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
                    editBtn.innerHTML = '<img src="assets/img/edit.svg" alt="編集" style="width:16px; height:16px;">';
                    editBtn.onclick = () => startEditMessage(m, true);
                    group.appendChild(editBtn);
                }

                group.appendChild(info);
                container.appendChild(group);
            });

            if (dms.length > 0) {
                const latest = dms[dms.length - 1];
                if (lastDmId !== 0 && latest.id > lastDmId && latest.sender_id != currentUserId) {
                    sendNotification(`新着DM: ${latest.username}`, latest.content);
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
                if (!isDmMode && currentThreadId == msg.thread_id) {
                    loadMessages();
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

            const registration = await navigator.serviceWorker.register('sw.js');
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
            initRealtime();
            initPush();

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

            // Polling (Reduced/Removed except for status)
            setInterval(() => {
                // We keep status update as it's not strictly real-time message dependent
                // fetchTypingUsers(); // Replaced by Socket.io
            }, 5000);
        });

        function sendNotification(title, body) {
            if (!isWindowFocused && Notification.permission === 'granted') {
                new Notification(title, {
                    body,
                    icon: 'SYCS_favicon.svg'
                });
            }
        }
    </script>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="js/webrtc.js"></script>
    <script src="js/locate.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // GPS 位置情報取得の初期化
            locationManager.init('gps-status', 1000);

        });
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
        async function searchMessages() {
            const queryInput = document.getElementById('search-input');
            const keyword = queryInput ? queryInput.value.trim() : '';
            if (!keyword) return;

            const res = await api(`search_messages&thread_id=${currentThreadId}&keyword=${encodeURIComponent(keyword)}`);
            const list = document.getElementById('search-results-list');
            const overlay = document.getElementById('search-results-overlay');

            list.innerHTML = '';
            overlay.style.display = 'flex';

            if (res.length === 0) {
                list.innerHTML = '<div style="padding:10px; color:var(--text-secondary);">結果が見つかりませんでした</div>';
                return;
            }

            res.forEach(m => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.innerHTML = `
                <div style="font-size:0.75rem; color:var(--accent-color); font-weight:700;">${m.username}</div>
                <div style="font-size:0.85rem; margin:4px 0;">${m.content}</div>
                <div style="font-size:0.65rem; opacity:0.6;">${m.created_at}</div>
            `;
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
                else loadMessages();
            } else {
                alert('編集に失敗しました');
            }
        }

        async function showAttachmentGallery() {
            const modal = document.getElementById('gallery-modal');
            const content = document.getElementById('gallery-content');
            content.innerHTML = '<div style="grid-column: 1/-1; text-align:center;">読み込み中...</div>';
            modal.showModal();

            const url = isDmMode ? `get_attachments&partner_id=${currentPartnerId}` : `get_attachments&thread_id=${currentThreadId}`;
            const files = await api(url);

            content.innerHTML = '';
            if (files.length === 0) {
                content.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:var(--text-secondary);">添付ファイルはありません</div>';
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
                    placeholder.innerHTML = '📄<div style="font-size:0.7rem; margin-top:8px; padding:0 4px; overflow:hidden; text-overflow:ellipsis; width:100%; text-align:center;">' + path.split('/').pop() + '</div>';
                    item.appendChild(placeholder);
                }
                content.appendChild(item);
            });
        }
    </script>
</body>

</html>
