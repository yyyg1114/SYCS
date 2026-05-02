<?php
/**
 * Database Initialization and Migrations
 */

function db_init($mysqli) {
    // 27-31: Cleanup
    $mysqli->query("DELETE FROM messages WHERE expires_at IS NOT NULL AND expires_at < NOW()");
    $mysqli->query("DELETE FROM direct_messages WHERE expires_at IS NOT NULL AND expires_at < NOW()");

    // 33-48: Basic Tables
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

    // 50-77: User table extensions
    $mysqli->query("ALTER TABLE users MODIFY COLUMN email VARCHAR(500)");
    $res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'email_hash'");
    if ($res->num_rows === 0) $mysqli->query("ALTER TABLE users ADD COLUMN email_hash VARCHAR(64) NULL AFTER email");
    
    $res = $mysqli->query("SHOW INDEX FROM users WHERE Key_name = 'idx_email_hash'");
    if ($res->num_rows === 0) $mysqli->query("CREATE INDEX idx_email_hash ON users(email_hash)");

    $cols = ['is_verified' => 'TINYINT DEFAULT 0', 'verification_token' => 'VARCHAR(255) NULL', 'reset_token' => 'VARCHAR(255) NULL', 'reset_expires' => 'DATETIME NULL'];
    foreach($cols as $col => $def) {
        $res = $mysqli->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($res->num_rows === 0) $mysqli->query("ALTER TABLE users ADD COLUMN $col $def");
    }

    // 79-93: Default data
    $res = $mysqli->query("SELECT id FROM users WHERE id = 1");
    if ($res->num_rows === 0) {
        $hashedAdminPass = password_hash('admin_pass', PASSWORD_DEFAULT);
        $email = 'admin@example.com';
        $encryptedEmail = SecurityUtil::encrypt($email);
        $emailHash = hash('sha256', $email);
        $mysqli->query("INSERT INTO users (id, email, email_hash, username, password, is_verified) VALUES (1, '$encryptedEmail', '$emailHash', 'admin', '$hashedAdminPass', 1)");
    }
    $res = $mysqli->query("SELECT id FROM threads WHERE id = 1");
    if ($res->num_rows === 0) $mysqli->query("INSERT INTO threads (id, name, creator_id) VALUES (1, 'general', 1)");

    // 95-183: Additional tables
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

    // 185-210: Group Chat
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

    $res = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'group_thread_id'");
    if ($res->num_rows === 0) {
        $mysqli->query("ALTER TABLE messages ADD COLUMN group_thread_id INT DEFAULT NULL AFTER thread_id");
        $mysqli->query("ALTER TABLE messages ADD FOREIGN KEY (group_thread_id) REFERENCES group_threads(id) ON DELETE CASCADE");
    }

    // 215-374: More Migrations
    $migrations = [
        ['threads', 'creator_id', 'INT DEFAULT 1'],
        ['users', 'last_thread_id', 'INT DEFAULT 1'],
        ['messages', 'reply_to_id', 'INT DEFAULT NULL AFTER content'],
        ['messages', 'attachment_path', 'VARCHAR(255) DEFAULT NULL AFTER reply_to_id'],
        ['users', 'status', "ENUM('online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away') DEFAULT 'online' AFTER is_verified"],
        ['users', 'custom_status', 'VARCHAR(100) NULL AFTER status'],
        ['users', 'bio', 'TEXT NULL AFTER custom_status'],
        ['users', 'social_links', 'JSON NULL AFTER bio'],
        ['users', 'avatar_url', 'VARCHAR(500) NULL AFTER social_links'],
        ['users', 'banner_color', "VARCHAR(20) DEFAULT '#6366f1' AFTER avatar_url"],
        ['users', 'banner_url', 'VARCHAR(500) NULL AFTER banner_color'],
        ['users', 'profile_layout', "VARCHAR(50) DEFAULT 'classic' AFTER banner_url"],
        ['users', 'badges', 'JSON NULL AFTER profile_layout'],
        ['users', 'theme_preference', 'JSON NULL AFTER banner_color'],
        ['users', 'typing_thread_id', 'VARCHAR(50) DEFAULT NULL AFTER theme_preference'],
        ['users', 'typing_at', 'TIMESTAMP NULL AFTER typing_thread_id'],
        ['threads', 'category', "VARCHAR(50) DEFAULT 'General' AFTER name"],
        ['messages', 'is_edited', 'TINYINT(1) DEFAULT 0 AFTER is_pinned'],
        ['messages', 'expires_at', 'DATETIME NULL AFTER is_edited'],
        ['direct_messages', 'is_edited', 'TINYINT(1) DEFAULT 0 AFTER is_read'],
        ['direct_messages', 'expires_at', 'DATETIME NULL AFTER is_edited'],
        ['users', 'discord_id', 'VARCHAR(255) NULL AFTER banner_color'],
        ['users', 'google_id', 'VARCHAR(255) NULL AFTER discord_id'],
        ['users', 'apple_id', 'VARCHAR(255) NULL AFTER google_id'],
        ['users', 'outlook_id', 'VARCHAR(255) NULL AFTER apple_id'],
        ['users', 'notification_keywords', 'TEXT DEFAULT NULL AFTER theme_preference'],
        ['messages', 'is_pinned', 'TINYINT(1) DEFAULT 0 AFTER attachment_path']
    ];

    foreach($migrations as $m) {
        $res = $mysqli->query("SHOW COLUMNS FROM {$m[0]} LIKE '{$m[1]}'");
        if ($res->num_rows === 0) $mysqli->query("ALTER TABLE {$m[0]} ADD COLUMN {$m[1]} {$m[2]}");
    }

    $mysqli->query("CREATE TABLE IF NOT EXISTS user_notification_settings (
        user_id INT NOT NULL,
        target_type ENUM('thread', 'group', 'dm') NOT NULL,
        target_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, target_type, target_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

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

    $res = $mysqli->query("SHOW INDEX FROM threads WHERE Key_name = 'idx_thread_name_unique'");
    if ($res->num_rows === 0) $mysqli->query("CREATE UNIQUE INDEX idx_thread_name_unique ON threads(name)");
}
