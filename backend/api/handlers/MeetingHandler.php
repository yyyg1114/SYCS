<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * MeetingHandler
 * ビデオ通話ルーム・シグナリングを担当
 */
class MeetingHandler extends BaseHandler
{
    public function joinMeeting(): void
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id');
        $pid = $this->getPost('dm_partner_id');

        if ($tid) {
            $tidInt = (int)$tid;
            if (!$this->canAccessThread($tidInt)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot join meeting for this thread']);
                return;
            }
        } elseif ($pid) {
            $pidInt = (int)$pid;
            if (!$this->canAccessDm($pidInt)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot join meeting for this DM partner']);
                return;
            }
        }

        $name = $tid
            ? "thread_{$tid}"
            : "dm_" . min($this->userId, (int)$pid) . "_" . max($this->userId, (int)$pid);

        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO meeting_rooms (room_name, creator_id, thread_id, dm_partner_id) VALUES (?, ?, ?, ?)"
        );
        $tVal = $tid ? (int)$tid : null;
        $pVal = $pid ? (int)$pid : null;
        $stmt->bind_param("siii", $name, $this->userId, $tVal, $pVal);
        $stmt->execute();

        $roomId = $stmt->insert_id;
        if ($roomId === 0) {
            $sel = $this->mysqli->prepare("SELECT id FROM meeting_rooms WHERE room_name = ?");
            $sel->bind_param("s", $name);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $roomId = $row['id'] ?? 0;
            $sel->close();
        }
        $stmt->close();

        echo json_encode(['success' => true, 'room_name' => $name, 'room_id' => $roomId]);
    }

    public function sendSignaling(): void
    {
        $this->verifyCsrf();
        $rid        = (int)$this->getPost('room_id', 0);
        $receiverId = (int)$this->getPost('receiver_id', 0);
        $type       = $this->getPost('type');
        $content    = $this->getPost('content');

        if (!$this->canAccessMeetingRoom($rid)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Access denied to meeting room']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiiss", $rid, $this->userId, $receiverId, $type, $content);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    }

    public function getSignaling(): void
    {
        $roomId = (int)$this->getGet('room_id', 0);
        $lastId = (int)$this->getGet('last_id', 0);

        if (!$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT * FROM signaling WHERE room_id = ? AND receiver_id = ? AND id > ?"
        );
        $stmt->bind_param("iii", $roomId, $this->userId, $lastId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
