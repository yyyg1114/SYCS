<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * MeetingHandler
 * WebRTC ビデオ通話ルーム・シグナリング・参加者管理を担当
 */
class MeetingHandler extends BaseHandler
{
    public function joinMeeting(): void
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id');
        $gtid = $this->getPost('group_thread_id');
        $pid = $this->getPost('dm_partner_id');
        $roomType = 'thread';
        $name = null;

        $tVal = null;
        $gtVal = null;
        $dm1 = null;
        $dm2 = null;

        if ($gtid) {
            $gtidInt = (int)$gtid;
            if (!$this->isGroupParticipant($gtidInt)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot join meeting for this group']);
                return;
            }
            $roomType = 'group';
            $gtVal = $gtidInt;
            $name = "group_{$gtidInt}";
        } elseif ($pid) {
            $pidInt = (int)$pid;
            if (!$this->canAccessDm($pidInt)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot join meeting for this DM partner']);
                return;
            }
            $roomType = 'dm';
            $dm1 = min($this->userId, $pidInt);
            $dm2 = max($this->userId, $pidInt);
            $name = "dm_{$dm1}_{$dm2}";
        } elseif ($tid) {
            $tidInt = (int)$tid;
            if (!$this->canAccessThread($tidInt)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot join meeting for this thread']);
                return;
            }
            $roomType = 'thread';
            $tVal = $tidInt;
            $name = "thread_{$tidInt}";
        } elseif ($tid === '0' && $gtid === '0' && $pid === '0') {
            // Instant meeting fallback
            $roomType = 'instant';
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
            $name = "instant_" . substr($uuid, 0, 8);
        }

        $uuidVal = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $stmt = $this->mysqli->prepare(
            "INSERT IGNORE INTO meeting_rooms (room_uuid, room_name, room_type, thread_id, group_thread_id, dm_user_1, dm_user_2, creator_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssiiiii", $uuidVal, $name, $roomType, $tVal, $gtVal, $dm1, $dm2, $this->userId);
        $stmt->execute();

        $roomId = $stmt->insert_id;
        if ($roomId === 0) {
            $sel = $this->mysqli->prepare("SELECT id, room_uuid FROM meeting_rooms WHERE room_name = ?");
            $sel->bind_param("s", $name);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $roomId = (int)($row['id'] ?? 0);
            $uuidVal = $row['room_uuid'] ?? $uuidVal;
            $sel->close();
        }
        $stmt->close();

        // Record participant in meeting_participants
        $partStmt = $this->mysqli->prepare(
            "INSERT INTO meeting_participants (room_id, user_id, joined_at) VALUES (?, ?, NOW())"
        );
        $partStmt->bind_param("ii", $roomId, $this->userId);
        $partStmt->execute();
        $partStmt->close();

        echo json_encode([
            'success' => true,
            'room_id' => $roomId,
            'room_uuid' => $uuidVal,
            'room_name' => $name,
            'room_type' => $roomType,
            'user_id' => $this->userId
        ]);
    }

    public function leaveMeeting(): void
    {
        $this->verifyCsrf();
        $roomId = (int)$this->getPost('room_id', 0);

        if ($roomId > 0) {
            $stmt = $this->mysqli->prepare(
                "UPDATE meeting_participants SET left_at = NOW() WHERE room_id = ? AND user_id = ? AND left_at IS NULL"
            );
            $stmt->bind_param("ii", $roomId, $this->userId);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['success' => true]);
    }

    public function getMeeting(): void
    {
        $roomId = (int)$this->getGet('room_id', 0);
        if (!$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT id, room_uuid, room_name, room_type, thread_id, group_thread_id, creator_id, created_at, closed_at FROM meeting_rooms WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$room) {
            http_response_code(444);
            echo json_encode(['success' => false, 'error' => 'Room not found']);
            return;
        }

        echo json_encode(['success' => true, 'room' => $room]);
    }

    public function getMeetingParticipants(): void
    {
        $roomId = (int)$this->getGet('room_id', 0);
        if (!$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT p.user_id, u.username, u.avatar_url, p.joined_at
        FROM meeting_participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.room_id = ? AND p.left_at IS NULL"
        );
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'participants' => $participants]);
    }

    public function issueTurnCredentials(): void
    {
        $this->verifyCsrf();
        $roomId = (int)$this->getPost('room_id', 0);
        if (!$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        // Generate short-lived TURN credentials or STUN servers fallback
        $iceServers = [
            ['urls' => 'stun:stun.l.google.com:19302'],
            ['urls' => 'stun:stun1.l.google.com:19302']
        ];

        // If turn environment variable configured
        $turnSecret = getenv('TURN_SECRET');
        $turnDomain = getenv('TURN_DOMAIN');

        if ($turnDomain && !$turnSecret) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'TURN configuration is incomplete'
            ]);
            return;
        }

        if ($turnDomain) {
            $ttl = 3600;
            $username = (time() + $ttl) . ":" . $this->userId;
            $credential = base64_encode(hash_hmac('sha1', $username, $turnSecret, true));
            $iceServers[] = [
                'urls' => "turn:{$turnDomain}:3478",
                'username' => $username,
                'credential' => $credential
            ];
        }

        echo json_encode(['success' => true, 'iceServers' => $iceServers]);
    }

    public function sendSignaling(): void
    {
        $this->verifyCsrf();
        $rid        = (int)$this->getPost('room_id', 0);
        $receiverId = (int)$this->getPost('receiver_id', 0);
        $type       = $this->getPost('type');
        $content    = $this->getPost('content');

        if (!$this->canAccessMeetingRoom($rid, $this->userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Access denied to meeting room']);
            return;
        }

        if ($receiverId <= 0 || !$this->canAccessMeetingRoom($rid, $receiverId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Invalid or unauthorized receiver']);
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

    /**
     * Issue a signed HMAC-SHA256 token for WebRTC signaling server authentication.
     */
    public function issueJoinToken(): void
    {
        $this->verifyCsrf();
        $roomId = (int)$this->getPost('room_id', 0);
        $roomName = $this->getPost('room_name', '');

        if ($roomId > 0 && empty($roomName)) {
            $stmt = $this->mysqli->prepare("SELECT room_name FROM meeting_rooms WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $roomId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) {
                $roomName = $res['room_name'];
            }
        }

        if (empty($roomName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing room identification']);
            return;
        }

        // Validate user access to room
        if ($roomId > 0 && !$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Cannot access this meeting room']);
            return;
        }

        $secret = getenv('SIGNALING_SECRET') ?: 'sycs-default-signaling-secret-change-me-in-prod';
        $exp = time() + 120; // Token valid for 2 minutes
        $payload = [
            'uid' => (int)$this->userId,
            'rid' => (string)$roomName,
            'exp' => $exp
        ];

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $sigEncoded = $this->base64UrlEncode($signature);

        $token = $payloadEncoded . '.' . $sigEncoded;

        echo json_encode([
            'success' => true,
            'token' => $token,
            'expires_at' => $exp,
            'room_name' => $roomName
        ]);
    }

    /**
     * Close meeting (Creator or Authorized)
     */
    public function closeMeeting(): void
    {
        $this->verifyCsrf();
        $roomId = (int)$this->getPost('room_id', 0);

        if ($roomId <= 0 || !$this->canAccessMeetingRoom($roomId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $stmt = $this->mysqli->prepare("UPDATE meeting_rooms SET closed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
    }

    /**
     * Join meeting by meeting ID/UUID & password (sanitized version of meetings.php join_by_id)
     */
    public function joinByMeetingId(): void
    {
        $this->verifyCsrf();
        $meetingId = trim($this->getPost('meeting_id', ''));
        $passcode = trim($this->getPost('passcode', ''));

        if (empty($meetingId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Meeting ID is required']);
            return;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT id, room_uuid, room_name, room_type, passcode_hash, is_active FROM meeting_rooms WHERE (id = ? OR room_uuid = ? OR room_name = ?) AND closed_at IS NULL LIMIT 1"
        );
        $midInt = (int)$meetingId;
        $stmt->bind_param("iss", $midInt, $meetingId, $meetingId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$room) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Meeting not found or already closed']);
            return;
        }

        if (!empty($room['passcode_hash']) && !password_verify($passcode, $room['passcode_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid passcode']);
            return;
        }

        // Record participant
        $partStmt = $this->mysqli->prepare(
            "INSERT INTO meeting_participants (room_id, user_id, joined_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE left_at = NULL"
        );
        $partStmt->bind_param("ii", $room['id'], $this->userId);
        $partStmt->execute();
        $partStmt->close();

        echo json_encode([
            'success' => true,
            'room_id' => $room['id'],
            'room_uuid' => $room['room_uuid'],
            'room_name' => $room['room_name'],
            'room_type' => $room['room_type']
        ]);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
