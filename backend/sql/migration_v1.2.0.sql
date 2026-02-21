-- migration_v1.2.0.sql
-- セキュリティ強化: ログイン試行回数制限テーブルの追加

USE SYCS_suchgamer;

-- ログイン試行履歴テーブル
-- identifier: ユーザー名またはIPアドレス（どちらも記録）
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(255) NOT NULL COMMENT 'ユーザー名またはIPアドレス',
    attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 古い試行記録を定期的に自動削除するためのイベント（任意）
-- MySQL Event Scheduler が有効な場合に機能する
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS cleanup_login_attempts
--     ON SCHEDULE EVERY 1 HOUR
--     DO DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
