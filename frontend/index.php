<?php

// 1. Secure Session Settings (Must be before session_start)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Default to current domain
    'secure' => isset($_SERVER['HTTPS']), // Only over HTTPS if available
    'httponly' => true, // JavaScript cannot access session cookie
    'samesite' => 'Strict' // Prevent CSRF via cross-site cookies
]);
session_start();

// 2. HTTP Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
// Content Security Policy: Allow own content, images, basic styles.
// Adjust 'unsafe-inline' based on needs (needed here for simple style attributes/script blocks).
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    $mysqli->query("ALTER TABLE users ADD COLUMN status ENUM('online', 'busy', 'away', 'offline') DEFAULT 'online' AFTER is_verified");
}
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'custom_status'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN custom_status VARCHAR(100) NULL AFTER status");
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

    if ($action === 'update_status') {
        verify_csrf();
        $status = $_POST['status'] ?? 'online';
        $customStatus = $_POST['custom_status'] ?? null;
        $allowed = ['online', 'busy', 'away', 'offline'];
        
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

// --- API Logic (AJAX Handlers) ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'get_threads') {
        $res = $mysqli->query("SELECT * FROM threads ORDER BY created_at DESC");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'create_thread') {
        verify_csrf(); // Enforce CSRF Check
        $name = $_POST['name'] ?? '';
        if ($name) {
            $stmt = $mysqli->prepare("INSERT INTO threads (name, creator_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $userId);
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
                $upd = $mysqli->prepare("UPDATE threads SET name = ? WHERE id = ?");
                $upd->bind_param("si", $newName, $threadId);
                $upd->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Forbidden']);
            }
        } else {
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
                // Delete messages first (if no CASCADE) - Assuming CASCADE or manual cleanup
                $mysqli->query("DELETE FROM messages WHERE thread_id = $threadId");
                $del = $mysqli->prepare("DELETE FROM threads WHERE id = ?");
                $del->bind_param("i", $threadId);
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

    if ($action === 'get_messages') {
        $threadId = $_GET['thread_id'] ?? 0;
        $stmt = $mysqli->prepare("
            SELECT m.*, u.username, u.status, r.content as reply_content, ru.username as reply_username
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            LEFT JOIN messages r ON m.reply_to_id = r.id
            LEFT JOIN users ru ON r.user_id = ru.id
            WHERE m.thread_id = ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        $stmt->close();
        exit;
    }

    if ($action === 'get_dm_partners') {
        // Get users I have sent to OR received from
        $query = "
            SELECT DISTINCT u.id, u.username 
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
        // Logic to search all users to start new DM
        $res = $mysqli->query("SELECT id, username FROM users WHERE id != $userId");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($action === 'get_direct_messages') {
        $partnerId = $_GET['partner_id'] ?? 0;
        $stmt = $mysqli->prepare("
            SELECT dm.*, u.username 
            FROM direct_messages dm
            JOIN users u ON dm.sender_id = u.id
            WHERE (dm.sender_id = ? AND dm.receiver_id = ?) 
               OR (dm.sender_id = ? AND dm.receiver_id = ?)
            ORDER BY dm.created_at ASC
        ");
        $stmt->bind_param("iiii", $userId, $partnerId, $partnerId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
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
                            $attachmentPath = 'frontend/uploads/' . $pngName;
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
                        $attachmentPath = 'frontend/uploads/' . $newFileName;
                    }
                }
            } else {
                echo json_encode(['error' => 'Invalid file type or content']);
                exit;
            }
        }

        if (($receiverId && $content !== '') || ($receiverId && $attachmentPath)) {
            $stmt = $mysqli->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $userId, $receiverId, $content, $attachmentPath);
            $stmt->execute();
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
                            $attachmentPath = 'frontend/uploads/' . $pngName;
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
                        $attachmentPath = 'frontend/uploads/' . $newFileName;
                    }
                }
            } else {
                echo json_encode(['error' => 'Invalid file type or content']);
                exit;
            }
        }

        if (($threadId && $content !== '') || ($threadId && $attachmentPath)) {
            $stmt = $mysqli->prepare("INSERT INTO messages (thread_id, user_id, content, reply_to_id, attachment_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisis", $threadId, $userId, $content, $replyToId, $attachmentPath);
            $stmt->execute();
            echo json_encode(['success' => true]);
            $stmt->close();
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
            SELECT u.id, u.username, u.status, u.custom_status,
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
}

// --- Auth Logic ---
$error = '';
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    // CSRF Check for Login
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'セッションが無効です。再読み込みしてください。';
    } else {
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';

        $stmt = $mysqli->prepare("SELECT id, username, password, is_verified, last_thread_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (password_verify($p, $row['password'])) {
                if ($row['is_verified'] != 1) {
                    $error = '本登録が完了していません。メール内のリンクを確認してください。';
                } else {
                    // Update hash if needed
                    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                        $newHash = password_hash($p, PASSWORD_DEFAULT);
                        $upd = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upd->bind_param("si", $newHash, $row['id']);
                        $upd->execute();
                        $upd->close();
                    }
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user'] = $row['username'];
                    $_SESSION['last_thread_id'] = $row['last_thread_id'] ?: 1;
                    header('Location: index.php');
                    exit;
                }
            } else {
                // Compatibility for transition ... (re-applying legacy logic here)
                if ($p === $row['password']) {
                    $newHash = password_hash($p, PASSWORD_DEFAULT);
                    $upd = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $newHash, $row['id']);
                    $upd->execute();
                    $upd->close();
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user'] = $row['username'];
                    $_SESSION['last_thread_id'] = $row['last_thread_id'] ?: 1;
                    header('Location: index.php');
                    exit;
                }
                $error = 'ログインに失敗しました。ユーザー名またはパスワードが正しくありません。';
            }
        } else {
            $error = 'ログインに失敗しました。ユーザー名またはパスワードが正しくありません。';
        }
    }
    if (isset($stmt))
        $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$isLoggedIn = isset($_SESSION['user']);
$currentUser = $_SESSION['user'] ?? null;
$initialThreadId = $_SESSION['last_thread_id'] ?? 1;

if ($isLoggedIn) {
    $stmt = $mysqli->prepare("SELECT last_thread_id, status, custom_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $initialThreadId = $row['last_thread_id'] ?: 1;
        $_SESSION['last_thread_id'] = $initialThreadId;
        $currentUserStatus = $row['status'] ?: 'online';
        $currentUserCustomStatus = $row['custom_status'];
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
            background-color: #94a3b8; /* Default offline */
        }
        .status-online { background-color: #22c55e; }
        .status-busy { background-color: #ef4444; }
        .status-away { background-color: #eab308; }
        .status-offline { background-color: #94a3b8; }

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
            background: rgba(255,255,255,0.1);
        }
        .status-select option {
            background: #1e1e2e;
            color: white;
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <div class="auth-container">
            <div class="auth-card">
                <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
                <p>Shinjuku Yamabuki Chat System</p>
                <?php if ($error): ?>
                    <div style="color: #ef4444; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?>
                    </div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group"><label>ユーザー名</label><input type="text" name="username" required
                            placeholder="Username"></div>
                    <div class="form-group"><label>パスワード</label><input type="password" name="password" required
                            placeholder="••••••••"></div>
                    <button type="submit" class="btn-primary">ログイン</button>
                </form>
                <div style="margin-top:2rem; font-size:0.85rem; color:var(--text-secondary);">
                    <a href="forgot_password.php" style="margin-bottom: 0.5rem; display: block;">パスワードを忘れましたか？</a>
                    アカウントをお持ちでないですか？ <a href="signup.php">新規登録</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="app-container">
            <aside class="sidebar">
                <div class="sidebar-top">
                    <div class="logo-container"><img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo"><span style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v1.0.4</span></div>
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
                            <div class="avatar" id="global-user-avatar">?</div>
                            <div class="status-indicator status-<?= htmlspecialchars($currentUserStatus) ?>" id="global-status-indicator"></div>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($currentUser) ?></span>
                            <div class="status-select-container">
                                <select class="status-select" onchange="updateMyStatus(this.value)">
                                    <option value="online" <?= $currentUserStatus === 'online' ? 'selected' : '' ?>>応答可</option>
                                    <option value="busy" <?= $currentUserStatus === 'busy' ? 'selected' : '' ?>>取り込み中</option>
                                    <option value="away" <?= $currentUserStatus === 'away' ? 'selected' : '' ?>>離席中</option>
                                    <option value="offline" <?= $currentUserStatus === 'offline' ? 'selected' : '' ?>>オフライン</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="sidebar-actions">
                        <a href="delete_account.php" class="action-link">設定</a>
                        <a href="?logout=1" class="action-link" style="color:#f87171;">ログアウト</a>
                    </div>
                </div>
            </aside>

            <main class="main-content">
                <section id="threads-pane" class="content-pane active">
                    <div class="chat-area">
                        <header class="chat-header">
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
                            <div class="thread-actions" id="thread-actions-block" style="display:none; margin-left: auto;">
                                <button class="icon-btn" onclick="editCurrentThread()" title="編集">✏️</button>
                                <button class="icon-btn" onclick="deleteCurrentThread()" title="削除"
                                    style="color:red;">🗑️</button>
                            </div>
                        </header>
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

                        <div class="chat-input-area">
                            <div class="input-wrapper">
                                <textarea id="msg-input" class="chat-input" placeholder="メッセージを送信... (Shift+Enterで改行)"
                                    rows="1" onkeydown="handleInputKey(event)"></textarea>
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
                        <div id="create-thread-toggle-container"
                            style="padding: 20px; border-top: 1px solid var(--border-color);">
                            <button onclick="showCreateThread()" class="btn-primary" style="width:100%;">+ 新規スレッド作成</button>
                        </div>
                        <div id="create-thread-area" class="create-thread-area" style="border-top: none;">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <span style="font-weight:600; font-size:0.9rem;">新規スレッド</span>
                                <div class="close-btn" onclick="hideCreateThread()">✕</div>
                            </div>
                            <input type="text" id="new-thread-name" class="create-input" placeholder="新スレッド名">
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
                            <h3>Friend Hub</h3>
                            <div style="margin-left:auto; display:flex; gap:10px;">
                                <button class="btn-primary" onclick="showAddFriendModal()">フレンド申請</button>
                                <button class="btn-primary" onclick="showPendingRequestsModal()"
                                    id="btn-pending-req">フレンド承認</button>
                                <button class="btn-secondary" style="background-color:#333;"
                                    onclick="showBlockedModal()">ブロック一覧</button>
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
                            <div class="thread-info">
                                <span class="thread-icon">@</span>
                                <h3 class="thread-name" id="current-dm-partner-name">Select a user</h3>
                            </div>
                            <!-- Block Button in Chat -->
                            <button class="icon-btn" onclick="blockCurrentPartner()" title="ブロック"
                                style="margin-left:auto; color:#ef4444;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                                </svg>
                            </button>
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

                        <div class="chat-input-area" id="dm-chat-area">
                            <div class="input-wrapper">
                                <textarea id="dm-msg-input" class="chat-input" placeholder="DMを送信..." rows="1"
                                    onkeydown="handleDmInputKey(event)"></textarea>
                                <button class="icon-btn" onclick="document.getElementById('dm-file-input').click()"
                                    style="margin-right:5px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                    </svg>
                                </button>
                                <input type="file" id="dm-file-input" hidden onchange="handleDmFiles(this.files)">
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
                        <div id="user-search-results" class="thread-list" style="max-height:200px; overflow-y:auto;"></div>
                        <div class="modal-actions" style="margin-top:10px; text-align:right;">
                            <button class="btn-secondary"
                                onclick="document.getElementById('add-friend-modal').close()">閉じる</button>
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
                <section id="favorites-pane" class="content-pane" style="display:none;">
                    <aside class="thread-browser active"
                        style="margin-left:0; border-right:1px solid var(--border-color); display:block; position:relative;">
                        <div class="panel-header">
                            <span>お気に入りスレッド</span>
                        </div>
                        <div id="fav-thread-list" class="thread-list"></div>
                    </aside>
                </section>
            </main>
        </div>

        <script>
            let currentThreadId = <?= (int) ($initialThreadId ?? 1) ?>;
            let currentThreadCreatorId = <?= (int) ($currentThreadCreatorId ?? 0) ?>;
            const currentUserId = <?= (int) $_SESSION['user_id'] ?>;
            const currentUserName = "<?= htmlspecialchars($currentUser) ?>";
            // DM State
            let currentPartnerId = null;
            let dmFileToUpload = null;
            let isDmMode = false;
            const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
            let replyToId = null;
            let fileToUpload = null;

            // DOM Elements
            const msgInput = document.getElementById('msg-input');
            const replyBar = document.getElementById('reply-bar');
            const uploadPreview = document.getElementById('upload-preview');
            const previewContent = document.getElementById('preview-content');

            // --- Markdown logic removed for strict security via innerText ---

            function getAvatarElement(name, status = 'none') {
                const initial = name ? name.charAt(0).toUpperCase() : '?';
                const colors = ['#6366f1', '#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#3b82f6'];
                const colorIdx = (name ? name.length : 0) % colors.length;

                const container = document.createElement('div');
                container.className = 'avatar-container';

                const div = document.createElement('div');
                div.className = 'avatar';
                div.style.background = colors[colorIdx];
                div.innerText = initial;
                container.appendChild(div);

                if (status !== 'none') {
                    const indicator = document.createElement('div');
                    indicator.className = `status-indicator status-${status}`;
                    container.appendChild(indicator);
                }

                return container;
            }

            async function updateMyStatus(status) {
                const body = new FormData();
                body.append('status', status);
                const res = await api('update_status', 'POST', body);
                if (res.success) {
                    const indicator = document.getElementById('global-status-indicator');
                    indicator.className = `status-indicator status-${status}`;
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
                threads.forEach(t => {
                    const item = document.createElement('div');
                    item.className = `thread-item ${t.id == currentThreadId ? 'active' : ''}`;
                    item.textContent = '# ' + t.name;
                    item.onclick = () => switchThread(t.id, t.name, t.creator_id);
                    list.appendChild(item);
                });
            }

            async function switchThread(id, name, creatorId) {
                currentThreadId = id;
                currentThreadCreatorId = creatorId;
                updateThreadActions();
                document.getElementById('current-thread-name').innerText = name;
                document.querySelectorAll('.thread-item').forEach(el => {
                    el.classList.remove('active');
                    if (el.textContent === '# ' + name) el.classList.add('active');
                });

                const container = document.getElementById('message-container');
                container.innerText = '';
                const h2 = document.createElement('h2');
                h2.innerText = '読込中...';
                const div = document.createElement('div');
                div.className = 'empty-state';
                div.appendChild(h2);
                container.appendChild(div);
                cancelReply();
                cancelUpload();
                loadMessages();
                checkFavoriteStatus(); // Check fav status on switch
                api(`set_last_thread&thread_id=${id}`);
            }

            function updateThreadActions() {
                const block = document.getElementById('thread-actions-block');
                if (parseInt(currentThreadCreatorId) === parseInt(currentUserId)) {
                    block.style.display = 'flex';
                } else {
                    block.style.display = 'none';
                }
            }

            async function editCurrentThread() {
                const newName = prompt("新しいスレッド名:", document.getElementById('current-thread-name').innerText);
                if (newName && newName.trim() !== "") {
                    const body = new FormData();
                    body.append('thread_id', currentThreadId);
                    body.append('name', newName.trim());
                    const res = await api('edit_thread', 'POST', body);
                    if (res.success) {
                        loadThreads();
                        switchThread(currentThreadId, newName.trim(), currentThreadCreatorId);
                    } else {
                        alert("編集に失敗しました: " + (res.error || 'Unknown'));
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

            async function loadMessages() {
                // Fetch friends statuses first to show them accurately in chat if needed, 
                // but get_messages JOIN users would be better. Let's update the query.
                const messages = await api(`get_messages&thread_id=${currentThreadId}`);
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
                group.appendChild(getAvatarElement(m.username, m.status || 'online'));

                const info = document.createElement('div');
                info.className = 'message-info';

                const header = document.createElement('div');
                header.className = 'message-header';

                const user = document.createElement('span');
                user.className = 'message-user';
                user.textContent = m.username;

                const time = document.createElement('span');
                time.className = 'message-time';
                time.textContent = m.created_at;

                // Actions
                const actions = document.createElement('div');
                actions.className = 'message-actions';

                // Always allow reply
                const replyBtn = document.createElement('button');
                replyBtn.className = 'msg-action-btn';
                replyBtn.innerText = '↩️';
                replyBtn.title = '返信';
                replyBtn.onclick = () => startReply(m.id, m.username, m.content);
                actions.appendChild(replyBtn);

                // Add Delete buttons only if owner
                if (m.username === currentUserName) {
                    const delBtn = document.createElement('button');
                    delBtn.className = 'msg-action-btn';
                    delBtn.innerText = '🗑️';
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
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            target.style.backgroundColor = 'rgba(99, 102, 241, 0.2)';
                            setTimeout(() => target.style.backgroundColor = '', 2000);
                        }
                    };
                    info.appendChild(quote);
                }

                // Content
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                if (m.content) contentDiv.innerText = m.content;

                if (m.attachment_path) {
                    const img = document.createElement('img');
                    img.src = m.attachment_path;
                    img.className = 'preview-img';
                    img.style.display = 'block';
                    img.style.marginTop = '10px';
                    img.onclick = () => window.open(m.attachment_path, '_blank');
                    contentDiv.appendChild(img);

                    // If it is a converted SVG (detected by filename convention or just providing download option for all images?)
                    // Requirement: "Download Original" for SVGs. 
                    // Since we convert SVG -> PNG, the path ends in .png. 
                    // We can check if a download link is viable or just always offer download for images.
                    // Let's deduce: If file is .png, check if we can download .svg? 
                    // No, that causes 404 for real PNGs. 
                    // Cleanest way: Just add a "Download" button for all files, but for converted ones it hits download.php? 
                    // Use the `download.php?file=...` for everything safely?
                    // Yes. Let's make the image click open the image, but add a small [Download] link below.

                    const dlLink = document.createElement('a');
                    const fileName = m.attachment_path.split('/').pop();
                    dlLink.href = 'download.php?file=' + fileName;
                    dlLink.target = '_blank'; // Trigger download
                    dlLink.innerText = '⬇️ ダウンロード';
                    dlLink.style.display = 'inline-block';
                    dlLink.style.fontSize = '0.75rem';
                    dlLink.style.marginTop = '5px';
                    dlLink.style.color = 'var(--accent-color)';
                    contentDiv.appendChild(dlLink);
                }

                info.appendChild(header);
                info.appendChild(contentDiv);
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

                const body = new FormData();
                body.append('thread_id', currentThreadId);
                body.append('content', content);
                if (replyToId) body.append('reply_to_id', replyToId);
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
                if (files.length > 0) handleFiles(files);
            });

            function handleFiles(files) {
                fileToUpload = files[0];
                previewContent.textContent = ''; // Clear safely
                // Preview
                if (fileToUpload.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.readAsDataURL(fileToUpload);
                    reader.onloadend = () => {
                        const img = document.createElement('img');
                        img.src = reader.result;
                        img.className = 'preview-img';
                        previewContent.appendChild(img);
                        uploadPreview.classList.add('active');
                    }
                } else {
                    const div = document.createElement('div');
                    div.style.padding = '10px';
                    div.style.border = '1px solid #444';
                    div.style.borderRadius = '8px';
                    div.innerText = '📄 ' + fileToUpload.name;
                    div.appendChild(document.createTextNode('📄 ' + fileToUpload.name));
                    previewContent.appendChild(div);
                    uploadPreview.classList.add('active');
                }
            }

            function cancelUpload() {
                fileToUpload = null;
                uploadPreview.classList.remove('active');
                previewContent.textContent = ''; // Clear safely
            }

            async function createThread() {
                const input = document.getElementById('new-thread-name');
                const name = input.value.trim();
                if (!name) return;
                const body = new FormData();
                body.append('name', name);
                const result = await api('create_thread', 'POST', body);
                
                if (result.error) {
                    alert('スレッドの作成に失敗しました: ' + result.error);
                    return;
                }
                
                await loadThreads();
                hideCreateThread();
                
                // Switch to the newly created thread
                if (result.id) {
                    switchThread(result.id, name, currentUserId);
                }
            }

            function toggleThreadBrowser() {
                document.getElementById('thread-browser').classList.toggle('active');
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

            function switchToDmChat(id, name) {
                currentPartnerId = id;
                document.getElementById('dm-hub-view').style.display = 'none';
                document.getElementById('dm-chat-view').style.display = 'flex';
                document.getElementById('current-dm-partner-name').innerText = name;
                loadDms();
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
                            ${getAvatarElement(f.username, f.status || 'offline').outerHTML}
                            <span>${f.username}</span>
                        </div>
                        <span style="font-size:0.8rem; color:var(--text-secondary);">
                            ${f.last_msg_at ? new Date(f.last_msg_at).toLocaleString() : '会話なし'}
                        </span>
                    `;
                    d.onclick = () => switchToDmChat(f.id, f.username);
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
                    d.innerHTML = `<span>${u.username} (ID:${u.id})</span>`;
                    const btn = document.createElement('button');
                    btn.innerText = '申請';
                    btn.className = 'btn-primary';
                    btn.style.padding = '2px 8px';
                    btn.style.fontSize = '0.75rem';
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

            async function loadDms() {
                if (!currentPartnerId) return;
                const dms = await api(`get_direct_messages&partner_id=${currentPartnerId}`);
                const container = document.getElementById('dm-message-container');
                const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                container.innerText = '';

                dms.forEach(m => {
                    const group = document.createElement('div');
                    group.className = 'message-group';
                    group.appendChild(getAvatarElement(m.username));

                    const info = document.createElement('div');
                    info.className = 'message-info';

                    const header = document.createElement('div');
                    header.className = 'message-header';

                    const user = document.createElement('span');
                    user.className = 'message-user';
                    user.textContent = m.username;

                    const time = document.createElement('span');
                    time.className = 'message-time';
                    time.textContent = m.created_at;

                    header.appendChild(user);
                    header.appendChild(time);

                    const contentDiv = document.createElement('div');
                    contentDiv.className = 'message-content';
                    if (m.content) contentDiv.innerText = m.content;

                    if (m.attachment_path) {
                        const img = document.createElement('img');
                        img.src = m.attachment_path;
                        img.className = 'preview-img';
                        img.style.display = 'block';
                        img.style.marginTop = '10px';
                        img.onclick = () => window.open(m.attachment_path, '_blank');
                        contentDiv.appendChild(img);
                    }

                    info.appendChild(header);
                    info.appendChild(contentDiv);
                    group.appendChild(info);
                    container.appendChild(group);
                });
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
                    d.innerText = u.username;
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

                const body = new FormData();
                body.append('receiver_id', currentPartnerId);
                body.append('content', content);
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

            document.addEventListener('DOMContentLoaded', () => {
                const avatarEl = document.getElementById('global-user-avatar');
                if (avatarEl) {
                    avatarEl.innerText = currentUserName ? currentUserName.charAt(0).toUpperCase() : '?';
                }

                // Initial Load
                loadThreads();
                if (isDmMode && currentPartnerId) loadDms();
                else if (!isDmMode && currentThreadId) loadMessages();

                // Also update thread actions logic initially
                updateThreadActions();

                // Polling
                setInterval(() => {
                    if (isDmMode && currentPartnerId) loadDms();
                    else if (!isDmMode && currentThreadId) loadMessages();
                }, 3000);
            });
        </script>
    <?php endif; ?>
</body>

</html>
