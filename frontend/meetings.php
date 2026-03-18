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
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .control-btn.active {
            background: var(--accent);
        }

        .hangup {
            background: #ef4444;
        }

        .hangup:hover {
            background: #ef4444;
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

        .info-item {
            font-family: monospace;
            font-size: 1rem;
            color: #818cf8;
            user-select: all;
        }
    </style>
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
                <span style="font-size: 1.2rem; color: #1a1a2e; font-weight: 1000;">i</span>
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


    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script>
        const currentUserId = <?= json_encode($userId) ?>;
        const currentUsername = <?= json_encode($username) ?>;
        const currentUserName = currentUsername; // For compatibility with webrtc.js logic

        let socket = null;
        function initSocket() {
            if (typeof io === 'undefined') return;
            socket = io("http://localhost:3000");
            socket.on('connect', () => {
                console.log("Connected to realtime server from meetings.php");
                socket.emit('register', currentUserId);
            });
        }
        initSocket();

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
                await meetingManager.start(lastCreatedMeeting.room_id);
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
                // Prep info display for joiner
                document.getElementById('info-id').innerText = mId;
                // Joint passive participants don't see the plain text password from server hash verification,
                // but since the browser user just typed it, we can use that.
                document.getElementById('info-pass').innerText = mPass;
                document.getElementById('info-pass-row').style.display = 'block';

                await meetingManager.start(res.room_id);
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

        // Full-featured WebRTC Manager for meetings.php (Synced with webrtc.js)
        class MeetingManager {
            constructor() {
                this.localStream = null;
                this.peers = {}; // peerUserId -> PC
                this.roomId = null;
                this.lastSignalingId = 0;
                this.pollingInterval = null;
                this.isMuted = false;
                this.isVideoOff = false;
                this.isScreenSharing = false;
                this.screenStream = null;
                this.iceServers = {
                    iceServers: [{
                            urls: "stun:stun.l.google.com:19302"
                        },
                        {
                            urls: "stun:stun1.l.google.com:19302"
                        },
                    ],
                };
            }

            async start(roomId) {
                this.roomId = roomId;
                try {
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: true
                    });
                    this.addVideoTrack(currentUserId, this.localStream, true);
                    document.getElementById('meeting-modal').showModal();
                    document.getElementById('join-card').style.display = 'none';

                    // Use Socket.io instead of polling
                    this.setupSocketEvents();
                    socket.emit("join_meeting", {
                        roomId: this.roomId,
                        userId: currentUserId,
                        username: currentUsername,
                    });

                    // Initial knock: get other members (Optional with user_joined_meeting)
                    const members = await api(`get_room_members&room_id=${this.roomId}`);
                    members.forEach(m => {
                        if (m.sender_id != currentUserId) this.initiateCall(m.sender_id);
                    });
                } catch (e) {
                    console.error(e);
                    alert("メディアデバイスへのアクセスに失敗しました。");
                }
            }

            setupSocketEvents() {
                if (!socket) return;
                socket.on("user_joined_meeting", (data) => {
                    if (data.userId != currentUserId) {
                        this.initiateCall(data.userId);
                    }
                });

                socket.on("user_left_meeting", (data) => {
                    this.removeVideoTrack(data.userId);
                });

                socket.on("webrtc_signal", async (data) => {
                    if (data.senderId == currentUserId) return;
                    await this.handleSignaling(data);
                });
            }

            async handleSignaling(msg) {
                const from = msg.senderId;
                const content = msg.content;
                if (msg.type === 'offer') {
                    const pc = this.getOrCreatePeer(from);
                    await pc.setRemoteDescription(new RTCSessionDescription(content));
                    const answer = await pc.createAnswer();
                    await pc.setLocalDescription(answer);
                    this.sendSignaling(from, 'answer', answer);
                } else if (msg.type === 'answer') {
                    const pc = this.peers[from];
                    if (pc) await pc.setRemoteDescription(new RTCSessionDescription(content));
                } else if (msg.type === 'candidate') {
                    const pc = this.peers[from];
                    if (pc) await pc.addIceCandidate(new RTCIceCandidate(content));
                }
            }

            async initiateCall(targetId) {
                const pc = this.getOrCreatePeer(targetId);
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                this.sendSignaling(targetId, 'offer', offer);
            }

            getOrCreatePeer(targetId) {
                if (this.peers[targetId]) return this.peers[targetId];
                const pc = new RTCPeerConnection(this.iceServers);

                // Add current active tracks (could be camera or screen)
                const activeStream = this.isScreenSharing ? this.screenStream : this.localStream;
                activeStream.getTracks().forEach(t => pc.addTrack(t, activeStream));

                pc.onicecandidate = e => {
                    if (e.candidate) this.sendSignaling(targetId, 'candidate', e.candidate);
                };
                pc.ontrack = e => {
                    const stream = e.streams[0] || new MediaStream([e.track]);
                    this.addVideoTrack(targetId, stream, false);
                };
                pc.onconnectionstatechange = () => {
                    if (["disconnected", "failed", "closed"].includes(pc.connectionState)) {
                        this.removeVideoTrack(targetId);
                    }
                };
                this.peers[targetId] = pc;
                return pc;
            }

            async sendSignaling(recvId, type, content) {
                if (socket) {
                    socket.emit("webrtc_signal", {
                        roomId: this.roomId,
                        targetId: recvId,
                        senderId: currentUserId,
                        type: type,
                        content: content,
                    });
                } else {
                    const body = new FormData();
                    body.append('room_id', this.roomId);
                    body.append('receiver_id', recvId);
                    body.append('type', type);
                    body.append('content', JSON.stringify(content));
                    await api('send_signaling', 'POST', body);
                }
            }

            addVideoTrack(userId, stream, isLocal) {
                const grid = document.getElementById('video-grid');
                let wrap = document.getElementById(`v-wrap-${userId}`);
                if (!wrap) {
                    wrap = document.createElement('div');
                    wrap.id = `v-wrap-${userId}`;
                    wrap.className = 'video-wrapper';
                    const v = document.createElement('video');
                    v.autoplay = true;
                    v.muted = isLocal;
                    v.setAttribute('playsinline', '');
                    v.playsInline = true;
                    const l = document.createElement('div');
                    l.className = 'video-label';
                    l.innerText = isLocal ? '自分' : `参加者 ${userId}`;
                    wrap.append(v, l);
                    grid.append(wrap);
                }
                const video = wrap.querySelector('video');
                if (video.srcObject !== stream) {
                    video.srcObject = stream;
                    video.play().catch(err => console.warn("Playback failed", err));
                }
            }

            removeVideoTrack(userId) {
                const wrap = document.getElementById(`v-wrap-${userId}`);
                if (wrap) wrap.remove();
                if (this.peers[userId]) {
                    this.peers[userId].close();
                    delete this.peers[userId];
                }
            }

            toggleMic() {
                this.isMuted = !this.isMuted;
                this.localStream.getAudioTracks().forEach(t => t.enabled = !this.isMuted);
                const micIcon = document.getElementById("mic-icon");
                if (micIcon) {
                    micIcon.src = this.isMuted ? "assets/img/mic_muted.svg" : "assets/img/mic.svg";
                }
                document.getElementById('toggle-mic').classList.toggle('muted', this.isMuted);
            }

            toggleVideo() {
                this.isVideoOff = !this.isVideoOff;
                this.localStream.getVideoTracks().forEach(t => t.enabled = !this.isVideoOff);
                const videoIcon = document.getElementById("video-icon");
                if (videoIcon) {
                    videoIcon.src = this.isVideoOff ? "assets/img/camera_off.svg" : "assets/img/camera_on.svg";
                }
                document.getElementById('toggle-video').classList.toggle('muted', this.isVideoOff);
            }

            async toggleScreenShare() {
                if (this.isScreenSharing) {
                    this.stopScreenShare();
                } else {
                    await this.startScreenShare();
                }
            }

            async startScreenShare() {
                try {
                    this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                        video: true
                    });
                    this.isScreenSharing = true;
                    const screenTrack = this.screenStream.getVideoTracks()[0];

                    // Replace track for all peers
                    for (const userId in this.peers) {
                        const pc = this.peers[userId];
                        const videoSender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (videoSender) videoSender.replaceTrack(screenTrack);
                    }

                    // Update local preview
                    const localVideo = document.querySelector(`#v-wrap-${currentUserId} video`);
                    if (localVideo) localVideo.srcObject = this.screenStream;

                    screenTrack.onended = () => this.stopScreenShare();
                    document.getElementById('toggle-screen').classList.add('active');
                } catch (e) {
                    console.error("Screen share failed:", e);
                }
            }

            stopScreenShare() {
                if (!this.isScreenSharing) return;
                if (this.screenStream) {
                    this.screenStream.getTracks().forEach(t => t.stop());
                    this.screenStream = null;
                }
                this.isScreenSharing = false;
                const videoTrack = this.localStream.getVideoTracks()[0];

                for (const userId in this.peers) {
                    const pc = this.peers[userId];
                    const videoSender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (videoSender) videoSender.replaceTrack(videoTrack);
                }

                const localVideo = document.querySelector(`#v-wrap-${currentUserId} video`);
                if (localVideo) localVideo.srcObject = this.localStream;
                document.getElementById('toggle-screen').classList.remove('active');
            }

            async leave() {
                if (this.roomId && socket) {
                    socket.emit("leave_meeting", {
                        roomId: this.roomId,
                        userId: currentUserId,
                    });
                }

                // Clear meeting ID/Pass from DB on exit
                if (this.roomId) {
                    const body = new FormData();
                    body.append('room_id', this.roomId);
                    await api('delete_meeting', 'POST', body);
                }

                for (const userId in this.peers) {
                    this.peers[userId].close();
                }
                if (this.localStream) {
                    this.localStream.getTracks().forEach(t => t.stop());
                }

                if (socket) {
                    socket.off("user_joined_meeting");
                    socket.off("user_left_meeting");
                    socket.off("webrtc_signal");
                }
                location.reload();
            }
        }
        const meetingManager = new MeetingManager();
    </script>

</body>

</html>
