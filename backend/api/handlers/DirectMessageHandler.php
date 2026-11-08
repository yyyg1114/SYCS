<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * DirectMessageHandler
 * DM送受信・既読・パートナー一覧・未読カウントを担当
 */
class DirectMessageHandler extends BaseHandler
{
    public function getDirectMessages(): void
    {
        if (function_exists('db_cleanup_expired')) {
            db_cleanup_expired($this->mysqli);
        }

        $rawPid = $this->getGet('partner_id', $this->getGet('recipient_id', $this->getGet('receiver_id', $this->getGet('thread_id'))));
        $pid = 0;
        if (is_numeric($rawPid)) {
            $pid = (int)$rawPid;
        } elseif (is_string($rawPid) && str_starts_with($rawPid, 'dm_')) {
            $parts = explode('_', $rawPid);
            if (count($parts) === 3) {
                $u1 = (int)$parts[1];
                $u2 = (int)$parts[2];
                $pid = ($this->userId === $u1) ? $u2 : (($this->userId === $u2) ? $u1 : 0);
            }
        }

        if ($pid <= 0 || $pid === $this->userId) {
            echo json_encode([]);
            return;
        }

        if (!$this->canAccessDm($pid)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'DM access denied']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT * FROM direct_messages
            WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC"
        );
        $stmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function sendDirectMessage(): void
    {
        $this->verifyCsrf();
        if (!$this->enforceRateLimit('send_direct_message', 30)) {
            return;
        }
        if (function_exists('db_cleanup_expired')) {
            db_cleanup_expired($this->mysqli);
        }
        $rid = (int)$this->getParam('receiver_id', $this->getParam('recipient_id', $this->getParam('partner_id', 0)));
        if ($rid <= 0 || $rid === $this->userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid receiver']);
            return;
        }

        if (!$this->canAccessDm($rid)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'DM access denied']);
            return;
        }

        $con  = $this->getPost('content', '');
        if (!$this->validateMessageContent($con, true)) {
            return;
        }
        $att  = $this->handleFileUpload();
        if (!$this->validateMessageContent($con, $att !== null)) {
            return;
        }

        $stmt = $this->mysqli->prepare(
            "INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iiss", $this->userId, $rid, $con, $att);
        $stmt->execute();
        $msgId = $stmt->insert_id;

        // 最新DMのIDをキャッシュファイルに記録
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        file_put_contents($cacheDir . '/last_dm.id', $msgId);

        // 送信者名を取得
        $uStmt = $this->mysqli->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->bind_param("i", $this->userId);
        $uStmt->execute();
        $senderName = $uStmt->get_result()->fetch_assoc()['username'] ?? 'User';

        $messageData = [
            'id'              => $msgId,
            'sender_id'       => $this->userId,
            'username'        => $senderName,
            'content'         => $con,
            'attachment_path' => $att,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        notifyRealtimeServer('new_dm', ['receiverId' => $rid, 'message' => $messageData]);

        sendPushNotification($rid, [
            'title' => 'New DM from ' . $senderName,
            'body'  => $con,
            'data'  => ['url' => 'index.php?tab=dm', 'senderId' => $this->userId],
        ]);

        echo json_encode(['success' => true]);
    }

    public function markDmsAsRead(): void
    {
        $this->verifyCsrf();
        $pid = (int)$this->getPost('partner_id', 0);
        if ($pid <= 0 || $pid === $this->userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid partner']);
            return;
        }

        if (!$this->canAccessDm($pid)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'DM access denied']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?"
        );
        $stmt->bind_param("ii", $pid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getDmPartners(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT u.id, u.username, u.avatar_url
        FROM users u
        JOIN direct_messages dm ON u.id = dm.receiver_id
        WHERE dm.sender_id = ? AND u.id != ?
        UNION
        SELECT u.id, u.username, u.avatar_url
        FROM users u
        JOIN direct_messages dm ON u.id = dm.sender_id
        WHERE dm.receiver_id = ? AND u.id != ?"
        );
        $stmt->bind_param("iiii", $this->userId, $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getUnreadDmCounts(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT sender_id, COUNT(*) as count FROM direct_messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
