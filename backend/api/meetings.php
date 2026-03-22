<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();
$currentUser = getCurrentUser();

$action = $_GET['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(["error" => "Action required"]);
    exit;
}

switch ($action) {
    case 'create':
        $meetingIdStr = number_format(mt_rand(100000000, 999999999), 0, '', '');
        $password = bin2hex(random_bytes(3)); // 6 characters
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        $roomName = "meeting_" . $meetingIdStr;

        $stmt = $mysqli->prepare("INSERT INTO meeting_rooms (meeting_id, password_hash, creator_id, room_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $meetingIdStr, $passHash, $currentUser['id'], $roomName);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "meeting_id" => $meetingIdStr,
                "password" => $password,
                "room_id" => $stmt->insert_id,
                "room_name" => $roomName
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database error"]);
        }
        break;

    case 'join':
        $data = json_decode(file_get_contents('php://input'), true);
        $mId = $data['meeting_id'] ?? '';
        $mPass = $data['password'] ?? '';

        $stmt = $mysqli->prepare("SELECT id, password_hash, room_name FROM meeting_rooms WHERE meeting_id = ?");
        $stmt->bind_param("s", $mId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($mPass, $row['password_hash'])) {
                echo json_encode([
                    "success" => true,
                    "room_id" => $row['id'],
                    "room_name" => $row['room_name']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Invalid password"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Meeting not found"]);
        }
        break;

    case 'send_signaling':
        $data = json_decode(file_get_contents('php://input'), true);
        $roomId = $data['room_id'] ?? null;
        $receiverId = $data['receiver_id'] ?? null;
        $type = $data['type'] ?? null;
        $content = $data['content'] ?? null;

        if (!$roomId || !$receiverId || !$type || !$content) {
            http_response_code(400);
            echo json_encode(["error" => "Missing signaling data"]);
            exit;
        }

        $stmt = $mysqli->prepare("INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $roomId, $currentUser['id'], $receiverId, $type, $content);
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database error"]);
        }
        break;

    case 'get_signaling':
        $roomId = $_GET['room_id'] ?? null;
        $lastId = $_GET['last_id'] ?? 0;

        if (!$roomId) {
            http_response_code(400);
            echo json_encode(["error" => "Room ID required"]);
            exit;
        }

        $stmt = $mysqli->prepare("SELECT s.*, u.username as sender_username FROM signaling s JOIN users u ON s.sender_id = u.id WHERE s.room_id = ? AND s.receiver_id = ? AND s.id > ? ORDER BY s.id ASC");
        $stmt->bind_param("iii", $roomId, $currentUser['id'], $lastId);
        $stmt->execute();
        echo json_encode(["success" => true, "signals" => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'get_members':
        $roomId = $_GET['room_id'] ?? null;
        if (!$roomId) {
            http_response_code(400);
            echo json_encode(["error" => "Room ID required"]);
            exit;
        }

        // Active members who sent signaling recently
        $stmt = $mysqli->prepare("SELECT DISTINCT s.sender_id, u.username FROM signaling s JOIN users u ON s.sender_id = u.id WHERE s.room_id = ? AND s.created_at > (NOW() - INTERVAL 10 SECOND) AND s.sender_id != ?");
        $stmt->bind_param("ii", $roomId, $currentUser['id']);
        $stmt->execute();
        echo json_encode(["success" => true, "members" => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Invalid action"]);
        break;
}
