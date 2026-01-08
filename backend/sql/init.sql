-- データベース作成
CREATE DATABASE IF NOT EXISTS tac_ops2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE tac_ops2;

-- ユーザー
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GPSログ
CREATE TABLE gps_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 無線ログ（radio.logフォーマット準拠）
CREATE TABLE radio_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  log_datetime DATETIME NOT NULL,         -- 日時
  source VARCHAR(50) NOT NULL,            -- 送信元
  type ENUM('INFO','MOVE','CHECK','WARN','CMD','COMMIT') NOT NULL, -- 種別
  type_seq INT NOT NULL,                  -- 種別内通し番号
  message TEXT NOT NULL,                  -- メッセージ
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- サンプルデータ（テスト用）
INSERT INTO radio_logs (log_datetime, source, type, type_seq, message) VALUES
('2025-01-04 08:00:00', 'Unit-A', 'MOVE', 1, '位置: 北西地区 移動開始'),
('2025-01-04 08:05:00', 'Command Center', 'CMD', 1, 'Unit-A、北西地区での捜索を開始してください'),
('2025-01-04 08:10:00', 'Unit-B', 'INFO', 1, '東地区での巡回を完了しました'),
('2025-01-04 08:15:00', 'Unit-A', 'CHECK', 1, 'コマンドセンター、現在地点でターゲット確認待ちです'),
('2025-01-04 08:20:00', 'Command Center', 'INFO', 2, '了解。Unit-A、その位置で待機してください'),
('2025-01-04 08:25:00', 'Unit-C', 'WARN', 1, '警告：不審な動きを検出しました'),
('2025-01-04 08:30:00', 'Unit-A', 'MOVE', 2, '北西地区より南西地区へ移動します'),
('2025-01-04 08:35:00', 'Unit-B', 'COMMIT', 1, '東地区の詳細調査が完了しました'),
('2025-01-04 08:40:00', 'Command Center', 'CMD', 2, '全ユニット、集合地点へ集結してください');

