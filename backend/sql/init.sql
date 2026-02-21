-- データベース作成
CREATE DATABASE IF NOT EXISTS SYCS_suchgamer
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE SYCS_suchgamer;

-- ユーザー
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(500) NOT NULL,
  email_hash VARCHAR(64) NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_verified TINYINT DEFAULT 0,
  verification_token VARCHAR(255) NULL,
  reset_token VARCHAR(255) NULL,
  reset_expires DATETIME NULL,
  last_thread_id INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_hash (email_hash)
);

-- セッション管理
CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_token VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- スレッド
CREATE TABLE IF NOT EXISTS threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  creator_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- メッセージ (チャットログ)
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT,
  reply_to_id INT DEFAULT NULL,
  attachment_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- ダイレクトメッセージ
CREATE TABLE IF NOT EXISTS direct_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT,
    attachment_path VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- フレンド
CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id_1 INT NOT NULL,
    user_id_2 INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id_1) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id_2) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id_1, user_id_2)
);

-- お気に入り
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    UNIQUE KEY unique_fav (user_id, thread_id)
);

-- ブロック
CREATE TABLE IF NOT EXISTS blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (blocker_id, blocked_id)
);

-- ログイン試行履歴（ブルートフォース対策）
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(255) NOT NULL COMMENT 'ユーザー名またはIPアドレス',
    attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 初期データ投入
-- 外部キー制約を満たすため、まずダミーユーザーを一人作成
-- パスワードは 'admin_pass' のハッシュ値
-- メールは 'admin@example.com' の暗号化+ハッシュ値 (固定キーでの例)
INSERT IGNORE INTO users (id, email, email_hash, username, password, is_verified) VALUES (1, 'V3oyZ3g4R0V5dFl0OWt3T285ODVqdz09OjpkMmZjNjE5ZDRmOTI0YzY4ZmZmM2ExY2M1NzkxZGY1ZA==', '73686866633633393033653139366361366266383636306331306361313431633534343063383030386134373462373037613531313264366635393433393863', 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 1);
-- 初期スレッド作成 (INSERT IGNORE で重複防止)
INSERT IGNORE INTO threads (id, name, creator_id) VALUES (1, 'general', 1);
