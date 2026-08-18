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
        $tid  = (int)$this->getPost('target_id', 0);
        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO friends (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')"
        );
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function sendFriendRequestAction(): void
    {
        $this->verifyCsrf();
        $tid = (int)$this->getPost('friend_id', $this->getPost('target_id', 0));
        if ($tid > 0) {
            $stmt = $this->mysqli->prepare(
                "INSERT IGNORE INTO friends (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')"
            );
            $stmt->bind_param("ii", $this->userId, $tid);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid friend ID']);
        }
    }

    public function handleFriendRequestAction(): void
    {
        $this->verifyCsrf();
        $rid = (int)$this->getPost('request_id', 0);
        $act = $this->getPost('action', '');
        if ($rid > 0) {
            if ($act === 'accept') {
                $stmt = $this->mysqli->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ?");
                $stmt->bind_param("ii", $rid, $this->userId);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true]);
            } elseif ($act === 'reject') {
                $stmt = $this->mysqli->prepare("DELETE FROM friends WHERE id = ? AND user_id_2 = ?");
                $stmt->bind_param("ii", $rid, $this->userId);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
        }
    }

    /**
     * [SECURITY FIX] prepared statement 化
     */
    public function acceptFriend(): void
    {
        $this->verifyCsrf();
        $rid  = (int)$this->getPost('request_id', 0);
        $stmt = $this->mysqli->prepare(
            "UPDATE friends SET status = 'accepted' WHERE id = ? AND user_id_2 = ?"
        );
        $stmt->bind_param("ii", $rid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
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
        $tid  = (int)$this->getPost('target_id', 0);
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
        $tid  = (int)$this->getPost('target_id', 0);
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
