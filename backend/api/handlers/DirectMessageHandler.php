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

        $pid = (int)$this->getGet('partner_id', 0);
        if ($pid <= 0 || $pid === $this->userId) {
            echo json_encode([]);
            return;
        }

        // Only allow access to users with an accepted friendship or a prior DM relationship.
        $friendStmt = $this->mysqli->prepare(
            "SELECT 1 FROM friends
            WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))
            AND status = 'accepted'
            LIMIT 1"
        );
        $friendStmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
        $friendStmt->execute();
        $friendCheck = $friendStmt->get_result()->fetch_assoc();
        $friendStmt->close();

        if (!$friendCheck) {
            $dmStmt = $this->mysqli->prepare(
                "SELECT 1 FROM direct_messages
                WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                LIMIT 1"
            );
            $dmStmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
            $dmStmt->execute();
            $dmExists = $dmStmt->get_result()->fetch_assoc();
            $dmStmt->close();

            if (!$dmExists) {
                echo json_encode([]);
                return;
            }
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
        if (function_exists('db_cleanup_expired')) {
            db_cleanup_expired($this->mysqli);
        }
        $rid = (int)$this->getParam('receiver_id', 0);
        if ($rid <= 0 || $rid === $this->userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid receiver']);
            return;
        }

        $friendStmt = $this->mysqli->prepare(
            "SELECT 1 FROM friends
            WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))
            AND status = 'accepted'
            LIMIT 1"
        );
        $friendStmt->bind_param("iiii", $this->userId, $rid, $rid, $this->userId);
        $friendStmt->execute();
        $friendExists = $friendStmt->get_result()->fetch_assoc();
        $friendStmt->close();

        if (!$friendExists) {
            $dmStmt = $this->mysqli->prepare(
                "SELECT 1 FROM direct_messages
                WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                LIMIT 1"
            );
            $dmStmt->bind_param("iiii", $this->userId, $rid, $rid, $this->userId);
            $dmStmt->execute();
            $dmExists = $dmStmt->get_result()->fetch_assoc();
            $dmStmt->close();

            if (!$dmExists) {
                echo json_encode(['success' => false, 'error' => 'Friendship required for DM']);
                return;
            }
        }

        $con  = $this->getPost('content', '');
        $att  = $this->handleFileUpload();

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

        $friendStmt = $this->mysqli->prepare(
            "SELECT 1 FROM friends
            WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))
            AND status = 'accepted'
            LIMIT 1"
        );
        $friendStmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
        $friendStmt->execute();
        $friendExists = $friendStmt->get_result()->fetch_assoc();
        $friendStmt->close();

        if (!$friendExists) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
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
