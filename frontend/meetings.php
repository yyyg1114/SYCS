<?php

/**
 * meetings.php - Meeting ID & Password based Meeting System
 * Separate from index.php for clean meeting management
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// --- Database Schema Support ---
// Ensure columns exist (Migration logic)
$mysqli->query("ALTER TABLE meeting_rooms ADD COLUMN IF NOT EXISTS meeting_id VARCHAR(20) UNIQUE AFTER id");
$mysqli->query("ALTER TABLE meeting_rooms ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) AFTER meeting_id");

// --- API Logic for meetings.php ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $apiAction = $_GET['api'];

    // Create a new meeting room with ID/Password
    if ($apiAction === 'create_instant_meeting') {
        $meetingIdStr = number_format(mt_rand(100000000, 999999999), 0, '', ''); // 9-digit ID
        $password = bin2hex(random_bytes(3)); // 6-char random password
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        $roomName = "meeting_" . $meetingIdStr;

        $stmt = $mysqli->prepare("INSERT INTO meeting_rooms (meeting_id, password_hash, creator_id, room_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $meetingIdStr, $passHash, $userId, $roomName);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'meeting_id' => $meetingIdStr,
                'password' => $password,
                'room_id' => $stmt->insert_id,
                'room_name' => $roomName
            ]);
        } else {
            echo json_encode(['error' => 'Could not create meeting room.']);
        }
        $stmt->close();
        exit;
    }

    // Join meeting room with ID/Password
    if ($apiAction === 'join_by_id') {
        $mId = $_POST['meeting_id'] ?? '';
        $mPass = $_POST['password'] ?? '';

        $stmt = $mysqli->prepare("SELECT id, password_hash, room_name FROM meeting_rooms WHERE meeting_id = ?");
        $stmt->bind_param("s", $mId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($mPass, $row['password_hash'])) {
                echo json_encode([
                    'success' => true,
                    'room_id' => $row['id'],
                    'room_name' => $row['room_name']
                ]);
            } else {
                echo json_encode(['error' => 'パスワードが違います']);
            }
        } else {
            echo json_encode(['error' => 'ミーティングIDが見つかりません']);
        }
        $stmt->close();
        exit;
    }

    // Get meeting info by room_id
    if ($apiAction === 'get_meeting_info') {
        $roomId = $_GET['room_id'] ?? 0;
        $stmt = $mysqli->prepare("SELECT meeting_id, password_hash FROM meeting_rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'meeting_id' => $row['meeting_id'],
                // Note: password_hash is not helpful for display, 
                // but in this simple demo we'll assume the client might need to know the room is valid.
                // Ideally, we'd store the plain password or a hint if needed.
                // Since mt_rand/bin2hex was used, we'll just show the ID for now.
            ]);
        } else {
            echo json_encode(['error' => 'Meeting not found']);
        }
        $stmt->close();
        exit;
    }

    // Delete meeting ID and password (cleanup)
    if ($apiAction === 'delete_meeting') {
        $roomId = $_POST['room_id'] ?? ($_GET['room_id'] ?? 0);
        // We only clear the meeting_id and password_hash to "deactivate" it
        $stmt = $mysqli->prepare("UPDATE meeting_rooms SET meeting_id = NULL, password_hash = NULL WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Could not clean up meeting.']);
        }
        $stmt->close();
        exit;
    }

    // Standard signaling (reuse from index.php if needed, but here's a copy for independence)
    if ($apiAction === 'send_signaling') {
        $roomId = $_POST['room_id'];
        $receiverId = $_POST['receiver_id'];
        $type = $_POST['type'];
        $content = $_POST['content'];

        $stmt = $mysqli->prepare("INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $roomId, $userId, $receiverId, $type, $content);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($apiAction === 'get_signaling') {
        $roomId = $_GET['room_id'];
        $lastId = $_GET['last_id'] ?? 0;

        $stmt = $mysqli->prepare("SELECT * FROM signaling WHERE room_id = ? AND receiver_id = ? AND id > ? ORDER BY id ASC");
        $stmt->bind_param("iii", $roomId, $userId, $lastId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // Get members in room
    if ($apiAction === 'get_room_members') {
        $roomId = $_GET['room_id'];
        // In this simple mode, we consider anyone who sent signaling recently as "in room"
        // Better: A real membership table. For now, let's keep it simple.
        $stmt = $mysqli->prepare("SELECT DISTINCT sender_id FROM signaling WHERE room_id = ? AND created_at > (NOW() - INTERVAL 10 SECOND)");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting SYCS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --accent: #6366f1;
            --glass: rgba(255, 255, 255, 0.05);
        }

        body {
            background: var(--bg-dark);
            color: white;
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            text-align: center;
        }

        .card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 2rem;
            font-weight: 700;
            color: #f8fafc;
        }

        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #94a3b8;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 1rem;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 12px;
            border: none;
            background: var(--accent);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
            margin-top: 1rem;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .secondary-btn {
            background: rgba(255, 255, 255, 0.1);
        }

        .divider {
            margin: 2rem 0;
            display: flex;
            align-items: center;
            color: #475569;
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #334155;
            margin: 0 1rem;
        }

        #result-area {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px dashed var(--accent);
            border-radius: 12px;
            display: none;
        }

        .meeting-info {
            font-family: monospace;
            font-size: 1.25rem;
            color: #818cf8;
            margin: 0.5rem 0;
        }

        /* Video Modal Overrides */
        #meeting-modal {
            background: #000;
            border: none;
            width: 100vw;
            height: 100vh;
            max-width: 100vw;
            max-height: 100vh;
            margin: 0;
            padding: 0;
            color: white;
        }

        .video-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1rem;
            height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .video-wrapper {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-label {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .meeting-controls {
            position: fixed !important;
            bottom: 30px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 16px !important;
            background: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            padding: 12px 24px !important;
            border-radius: 9999px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6) !important;
            z-index: 99999 !important;
            width: auto !important;
            height: auto !important;
            max-width: 90vw !important;
        }

        .meeting-controls .control-btn {
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            min-height: 48px !important;
            max-width: 48px !important;
            max-height: 48px !important;
            border-radius: 50% !important;
            border: none !important;
            background: rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }

        .meeting-controls .control-btn:hover {
            background: rgba(255, 255, 255, 0.25) !important;
            transform: translateY(-2px) scale(1.05) !important;
        }

        .meeting-controls .control-btn.active {
            background: #6366f1 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.muted {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.hangup-btn,
        .meeting-controls .control-btn.hangup,
        .meeting-controls .control-btn#hangup-btn {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.hangup-btn:hover,
        .meeting-controls .control-btn.hangup:hover,
        .meeting-controls .control-btn#hangup-btn:hover {
            background: #dc2626 !important;
        }

        .meeting-controls .control-btn img,
        .meeting-controls .control-btn svg {
            width: 22px !important;
            height: 22px !important;
            max-width: 22px !important;
            max-height: 22px !important;
            object-fit: contain !important;
            flex-shrink: 0 !important;
            filter: brightness(0) invert(1) !important;
        }

        /* Meeting Info Bar (Keep as it is custom for meetings.php) */
        #meeting-info-box {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            display: none;
            text-align: left;
        }

        #meeting-info-box h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        .status-badge.connected {
            background: rgba(34, 197, 94, 0.8);
            color: white;
        }

        .status-badge.connecting {
            background: rgba(234, 179, 8, 0.8);
            color: white;
        }

        .status-badge.failed,
        .status-badge.disconnected {
            background: rgba(239, 68, 68, 0.8);
            color: white;
        }
    </style>
    <script src="js/webrtc/media-manager.js"></script>
    <script src="js/webrtc/signaling-client.js"></script>
    <script src="js/webrtc/peer-connection-manager.js"></script>
    <script src="js/webrtc/meeting-ui.js"></script>
    <script src="js/webrtc/meeting-manager.js"></script>
</head>

<body>

    <div class="container" id="join-card">
        <div class="card">
            <h1>Meeting 参加</h1>

            <div class="input-group">
                <label>ミーティングID</label>
                <input type="text" id="m-id" placeholder="123 456 789">
            </div>
            <div class="input-group">
                <label>パスワード</label>
                <input type="password" id="m-pass" placeholder="パスワードを入力">
            </div>

            <button onclick="joinMeeting()">ミーティングに参加</button>

            <div class="divider">または</div>

            <button class="secondary-btn" onclick="createInstantMeeting()">新しいミーティングを作成</button>

            <div id="result-area">
                <p>ミーティングを作成しました！</p>
                <div id="meeting-details">
                    <div>ID: <span class="meeting-info" id="res-id"></span></div>
                    <div>PASS: <span class="meeting-info" id="res-pass"></span></div>
                </div>
                <button onclick="joinCreatedMeeting()" style="margin-top: 1rem;">作成した会議に入る</button>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="index.php" style="color: #94a3b8; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <span>← ホーム（チャット）に戻る</span>
                </a>
            </div>
        </div>
    </div>

    <dialog id="meeting-modal" class="modal meeting-modal" style="border:none; border-radius:12px; padding:0; background:#000; width:100vw; height:100vh; max-width:100vw; max-height:100vh; margin:0; overflow:hidden;">
        <div id="meeting-info-box">
            <h4>ミーティング情報</h4>
            <div>ID: <span id="info-id" class="info-item"></span></div>
            <div id="info-pass-row">PASS: <span id="info-pass" class="info-item"></span></div>
        </div>
        <div class="video-grid-container" id="video-grid">
            <!-- Local video and remote videos will be injected here -->
        </div>
        <div class="meeting-controls">
            <button class="control-btn" id="toggle-info" onclick="toggleMeetingInfo()" title="ミーティング詳細">
                <img id="info-icon" src="assets/img/info.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-mic" onclick="meetingManager.toggleMic()" title="マイク オン/オフ">
                <img id="mic-icon" src="assets/img/mic.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-video" onclick="meetingManager.toggleVideo()" title="カメラ オン/オフ">
                <img id="video-icon" src="assets/img/camera_on.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-screen" onclick="meetingManager.toggleScreenShare()" title="画面共有">
                <img id="screen-icon" src="assets/img/screen_share.svg" alt="">
            </button>
            <button class="control-btn hangup-btn" id="hangup-btn" style="background-color: white;" onclick="meetingManager.leave()" title="退席">
                <img id="hangup-icon" src="assets/img/hangup.svg" alt="" color="white">
            </button>
        </div>
    </dialog>


    <script>
        const currentUserId = <?= json_encode($userId) ?>;
        const currentUsername = <?= json_encode($username) ?>;

        async function api(action, method = 'GET', body = null) {
            const url = `meetings.php?api=${action}`;
            const options = {
                method
            };
            if (body) options.body = body;
            const res = await fetch(url, options);
            return res.json();
        }

        let lastCreatedMeeting = null;

        async function createInstantMeeting() {
            const res = await api('create_instant_meeting', 'POST');
            if (res.success) {
                lastCreatedMeeting = res;
                document.getElementById('res-id').innerText = res.meeting_id;
                document.getElementById('res-pass').innerText = res.password;
                document.getElementById('result-area').style.display = 'block';

                // Prep info display
                document.getElementById('info-id').innerText = res.meeting_id;
                document.getElementById('info-pass').innerText = res.password;
                document.getElementById('info-pass-row').style.display = 'block';
            } else {
                alert("エラー: " + res.error);
            }
        }

        async function joinCreatedMeeting() {
            if (lastCreatedMeeting) {
                await meetingManager.start({
                    roomId: lastCreatedMeeting.room_id
                });
            }
        }

        async function joinMeeting() {
            const mId = document.getElementById('m-id').value.replace(/\s/g, '');
            const mPass = document.getElementById('m-pass').value;

            const body = new FormData();
            body.append('meeting_id', mId);
            body.append('password', mPass);

            const res = await api('join_by_id', 'POST', body);
            if (res.success) {
                document.getElementById('info-id').innerText = mId;
                document.getElementById('info-pass').innerText = mPass;
                document.getElementById('info-pass-row').style.display = 'block';

                await meetingManager.start({
                    roomId: res.room_id
                });
            } else {
                alert(res.error);
            }
        }

        function toggleMeetingInfo() {
            const box = document.getElementById('meeting-info-box');
            const btn = document.getElementById('toggle-info');
            const isVisible = box.style.display === 'block';
            box.style.display = isVisible ? 'none' : 'block';
            btn.classList.toggle('active', !isVisible);
        }
    </script>
</body>

</html>
