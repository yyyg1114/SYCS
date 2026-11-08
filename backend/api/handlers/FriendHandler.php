<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * FriendHandler
 * フレンド申請・承認・一覧・ブロックを担当
 * セキュリティ修正:
 *   - requestFriend / acceptFriend / blockUser / unblockUser: query() + 変数直埋め → prepare() + バインド
 */
class FriendHandler extends BaseHandler
{
    /**
     * [SECURITY FIX] prepared statement 化
     */
    public function requestFriend(): void
    {
        $this->verifyCsrf();
        $this->createFriendRequest((int)$this->getPost('target_id', 0));
    }

    public function sendFriendRequestAction(): void
    {
        $this->verifyCsrf();

        $this->createFriendRequest((int)$this->getPost('friend_id', $this->getPost('target_id', 0)));
    }


    public function handleFriendRequestAction(): void
    {
        $this->verifyCsrf();
        $rid = (int)$this->getPost('request_id', 0);
        $act = $this->getPost('action', '');

        if ($rid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
            return;
        }

        if ($act === 'accept') {
            $senderId = $this->getFriendRequestSender($rid);
            if ($senderId <= 0 || $this->isUserBlocked($senderId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Friend request is unavailable']);
                return;
            }
            $stmt = $this->mysqli->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ?");
            $stmt->bind_param("ii", $rid, $this->userId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            return;
        }

        if ($act === 'reject') {
            $stmt = $this->mysqli->prepare("DELETE FROM friends WHERE id = ? AND user_id_2 = ?");
            $stmt->bind_param("ii", $rid, $this->userId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            return;
        }

        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

    /**
     * [SECURITY FIX] prepared statement 化
     */
    public function acceptFriend(): void
    {
        $this->verifyCsrf();
        $rid  = (int)$this->getPost('request_id', 0);
        $senderId = $rid > 0 ? $this->getFriendRequestSender($rid) : 0;
        if ($senderId <= 0 || $this->isUserBlocked($senderId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Friend request is unavailable']);
            return;
        }
        $stmt = $this->mysqli->prepare(
            "UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ?"
        );
        $stmt->bind_param("ii", $rid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function createFriendRequest(int $targetId): void
    {
        if ($targetId <= 0 || $targetId === (int)$this->userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid friend ID']);
            return;
        }
        if (!$this->enforceRateLimit('friend_request', 10)) {
            return;
        }

        $userStmt = $this->mysqli->prepare("SELECT 1 FROM users WHERE id = ? LIMIT 1");
        $userStmt->bind_param("i", $targetId);
        $userStmt->execute();
        $targetExists = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
        if (!$targetExists) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            return;
        }
        if ($this->isUserBlocked($targetId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Friend request is unavailable']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO friends (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')"
        );
        $stmt->bind_param("ii", $this->userId, $targetId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    }

    private function isUserBlocked(int $targetId): bool
    {
        if ($targetId <= 0) {
            return false;
        }
        $stmt = $this->mysqli->prepare(
            "SELECT 1 FROM blocked_users
             WHERE (blocker_id = ? AND blocked_id = ?)
                OR (blocker_id = ? AND blocked_id = ?)
             LIMIT 1"
        );
        $stmt->bind_param("iiii", $this->userId, $targetId, $targetId, $this->userId);
        $stmt->execute();
        $blocked = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$blocked;
    }

    private function getFriendRequestSender(int $requestId): int
    {
        $stmt = $this->mysqli->prepare("SELECT user_id_1 FROM friends WHERE id = ? AND user_id_2 = ? LIMIT 1");
        $stmt->bind_param("ii", $requestId, $this->userId);
        $stmt->execute();
        $sender = (int)($stmt->get_result()->fetch_assoc()['user_id_1'] ?? 0);
        $stmt->close();
        return $sender;
    }

    public function getFriendRequests(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT f.id, u.username FROM friends f
             JOIN users u ON f.user_id_1 = u.id
             WHERE f.user_id_2 = ? AND f.status = 'pending'"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getFriends(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT u.*
            FROM users u
            JOIN friends f ON u.id = f.user_id_2
            WHERE f.user_id_1 = ? AND f.status = 'accepted' AND u.id != ?
            UNION
            SELECT u.*
            FROM users u
            JOIN friends f ON u.id = f.user_id_1
            WHERE f.user_id_2 = ? AND f.status = 'accepted' AND u.id != ?"
        );
        $stmt->bind_param("iiii", $this->userId, $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * [SECURITY FIX] prepared statement 化
     */
    public function blockUser(): void
    {
        $this->verifyCsrf();
        // target_id / block_id 双方を受容（フロントエンドの命名揺れ対応）
        $tid  = (int)$this->getPost('target_id', $this->getPost('block_id', 0));
        if ($tid <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'target_id or block_id required']);
            return;
        }
        if ($tid === (int)$this->userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cannot block yourself']);
            return;
        }
        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    /**
     * [SECURITY FIX] prepared statement 化
     */
    public function unblockUser(): void
    {
        $this->verifyCsrf();
        // target_id / block_id 双方を受容（フロントエンドの命名揺れ対応）
        $tid  = (int)$this->getPost('target_id', $this->getPost('block_id', 0));
        if ($tid <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'target_id or block_id required']);
            return;
        }
        $stmt = $this->mysqli->prepare(
            "DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?"
        );
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getBlockedUsers(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT u.id, u.username FROM blocked_users b JOIN users u ON b.blocked_id = u.id WHERE b.blocker_id = ?"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
