<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * SseHandler
 * Server-Sent Events (SSE) ストリーミングエンドポイント
 * メッセージ・DM・ハートビートをクライアントへプッシュ配信する
 */
class SseHandler extends BaseHandler
{
    public function streamEvents(): void
    {
        // --- SSE Headers ---
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Access-Control-Allow-Origin: *');

        // 他の PHP リクエストをブロックしないようセッションを閉じる
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        set_time_limit(0);
        ignore_user_abort(true);

        // クライアントが送ってくる再接続用カーソル
        $lastMsgId = max(0, (int)$this->getGet('last_msg_id', 0));
        $lastDmId  = max(0, (int)$this->getGet('last_dm_id', 0));

        // 初回接続時は現時点の最大IDを起点にする
        if ($lastMsgId === 0) {
            $row = $this->mysqli->query("SELECT COALESCE(MAX(id),0) AS m FROM messages")->fetch_assoc();
            $lastMsgId = (int)($row['m'] ?? 0);
        }
        if ($lastDmId === 0) {
            $row = $this->mysqli->query("SELECT COALESCE(MAX(id),0) AS m FROM direct_messages")->fetch_assoc();
            $lastDmId = (int)($row['m'] ?? 0);
        }

        // 接続確認イベントを即時送出
        echo "event: connected\ndata: {\"ok\":true}\n\n";
        ob_flush();
        flush();

        $pollInterval   = 1500000; // 1.5秒
        $heartbeatEvery = 20;      // 20 × 1.5s = 30秒ごとにハートビート
        $tick           = 0;
        $cacheDir       = dirname(__DIR__, 2) . '/cache';

        while (true) {
            if (connection_aborted()) break;
            $tick++;

            // --- 1. 新しいスレッド/グループメッセージ（キャッシュ判定付き）---
            $globalLastMsgId = 0;
            $msgIdFile       = $cacheDir . '/last_message.id';
            if (file_exists($msgIdFile)) {
                $globalLastMsgId = (int)file_get_contents($msgIdFile);
            } else {
                $row = $this->mysqli->query("SELECT COALESCE(MAX(id),0) AS m FROM messages")->fetch_assoc();
                $globalLastMsgId = (int)($row['m'] ?? 0);
                if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
                file_put_contents($msgIdFile, $globalLastMsgId);
            }

            if ($lastMsgId < $globalLastMsgId) {
                $stmt = $this->mysqli->prepare(
                    "SELECT m.id, m.thread_id, m.group_thread_id, m.user_id,
                        m.content, m.attachment_path, m.reply_to_id,
                        m.created_at, u.username,
                        COALESCE(t.name, gt.name, '') AS thread_name
                    FROM messages m
                    JOIN users u ON m.user_id = u.id
                    LEFT JOIN threads t ON m.thread_id = t.id
                    LEFT JOIN group_threads gt ON m.group_thread_id = gt.id
                    WHERE m.id > ?
                    ORDER BY m.id ASC LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('i', $lastMsgId);
                    $stmt->execute();
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    foreach ($rows as $row) {
                        $eventName = $row['group_thread_id'] ? 'new_group_message' : 'new_message';
                        $payload   = [
                            'id'              => (int)$row['id'],
                            'threadId'        => (int)($row['thread_id']       ?? 0),
                            'groupThreadId'   => (int)($row['group_thread_id'] ?? 0),
                            'userId'          => (int)$row['user_id'],
                            'username'        => $row['username'],
                            'threadName'      => $row['thread_name'],
                            'content'         => $row['content'],
                            'attachment_path' => $row['attachment_path'],
                            'reply_to_id'     => $row['reply_to_id'],
                            'created_at'      => $row['created_at'],
                        ];
                        echo "event: {$eventName}\n";
                        echo "data: " . json_encode($payload) . "\n\n";
                        $lastMsgId = (int)$row['id'];
                    }
                }
            }

            // --- 2. 新しいDM（キャッシュ判定付き）---
            $globalLastDmId = 0;
            $dmIdFile       = $cacheDir . '/last_dm.id';
            if (file_exists($dmIdFile)) {
                $globalLastDmId = (int)file_get_contents($dmIdFile);
            } else {
                $row = $this->mysqli->query("SELECT COALESCE(MAX(id),0) AS m FROM direct_messages")->fetch_assoc();
                $globalLastDmId = (int)($row['m'] ?? 0);
                if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
                file_put_contents($dmIdFile, $globalLastDmId);
            }

            if ($lastDmId < $globalLastDmId) {
                $stmt = $this->mysqli->prepare(
                    "SELECT d.id, d.sender_id, d.receiver_id,
                        d.content, d.attachment_path, d.created_at,
                        u.username
                    FROM direct_messages d
                    JOIN users u ON d.sender_id = u.id
                    WHERE d.id > ?
                    AND (d.sender_id = ? OR d.receiver_id = ?)
                    ORDER BY d.id ASC LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('iii', $lastDmId, $this->userId, $this->userId);
                    $stmt->execute();
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    foreach ($rows as $row) {
                        $payload = [
                            'id'              => (int)$row['id'],
                            'sender_id'       => (int)$row['sender_id'],
                            'receiver_id'     => (int)$row['receiver_id'],
                            'userId'          => (int)$row['sender_id'],
                            'username'        => $row['username'],
                            'content'         => $row['content'],
                            'attachment_path' => $row['attachment_path'],
                            'created_at'      => $row['created_at'],
                        ];
                        echo "event: new_dm\n";
                        echo "data: " . json_encode($payload) . "\n\n";
                        $lastDmId = (int)$row['id'];
                    }
                }
            }

            // --- 3. ハートビート（接続維持）---
            if ($tick % $heartbeatEvery === 0) {
                echo "event: heartbeat\n";
                echo "data: {\"last_msg_id\":{$lastMsgId},\"last_dm_id\":{$lastDmId}}\n\n";
            }

            ob_flush();
            flush();
            usleep($pollInterval);
        }
    }
}
