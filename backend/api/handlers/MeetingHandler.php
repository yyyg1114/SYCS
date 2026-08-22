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
        $tid  = $this->getPost('thread_id');
        $pid  = $this->getPost('dm_partner_id');
        $name = $tid
            ? "thread_{$tid}"
            : "dm_" . min($this->userId, (int)$pid) . "_" . max($this->userId, (int)$pid);

        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO meeting_rooms (room_name, creator_id) VALUES (?, ?)"
        );
        $stmt->bind_param("si", $name, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true, 'room_name' => $name]);
    }

    public function sendSignaling(): void
    {
        $this->verifyCsrf();
        $rid        = $this->getPost('room_id', 0);
        $receiverId = $this->getPost('receiver_id');
        $type       = $this->getPost('type');
        $content    = $this->getPost('content');
        $stmt       = $this->mysqli->prepare(
            "INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiiss", $rid, $this->userId, $receiverId, $type, $content);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getSignaling(): void
    {
        $roomId = $this->getGet('room_id');
        $lastId = $this->getGet('last_id');
        $stmt   = $this->mysqli->prepare(
            "SELECT * FROM signaling WHERE room_id = ? AND receiver_id = ? AND id > ?"
        );
        $stmt->bind_param("iii", $roomId, $this->userId, $lastId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
