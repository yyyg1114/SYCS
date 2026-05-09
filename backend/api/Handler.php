<?php

/**
 * SYCS API Handler
 */
require_once __DIR__ . '/../helpers.php';

class ApiHandler
{
    private $mysqli;
    private $userId;
    private $csrfToken;

    public function __construct($mysqli, $userId, $csrfToken)
    {
        $this->mysqli = $mysqli;
        $this->userId = $userId;
        $this->csrfToken = $csrfToken;
    }

    public function handle($action): void
    {
        try {
            switch ($action) {
                case 'update_profile':
                    $this->updateProfile();
                    break;
                case 'push_subscribe':
                    $this->pushSubscribe();
                    break;
                case 'update_status':
                    $this->updateStatus();
                    break;
                case 'get_user_status':
                    $this->getUserStatus();
                    break;
                case 'get_user_profile':
                    $this->getUserProfile();
                    break;
                case 'get_friends_statuses':
                    $this->getFriendsStatuses();
                    break;
                case 'get_threads':
                    $this->getThreads();
                    break;
                case 'create_thread':
                    $this->createThread();
                    break;
                case 'edit_thread':
                    $this->editThread();
                    break;
                case 'delete_thread':
                    $this->deleteThread();
                    break;
                case 'toggle_reaction':
                    $this->toggleReaction();
                    break;
                case 'toggle_pin':
                    $this->togglePin();
                    break;
                case 'search_messages':
                    $this->searchMessages();
                    break;
                case 'update_typing_status':
                    $this->updateTypingStatus();
                    break;
                case 'get_typing_users':
                    $this->getTypingUsers();
                    break;
                case 'mark_dms_as_read':
                    $this->markDmsAsRead();
                    break;
                case 'edit_message':
                    $this->editMessage();
                    break;
                case 'get_attachments':
                    $this->getAttachments();
                    break;
                case 'get_messages':
                    $this->getMessages();
                    break;
                case 'get_dm_partners':
                    $this->getDmPartners();
                    break;
                case 'get_all_users':
                    $this->getAllUsers();
                    break;
                case 'create_group_thread':
                    $this->createGroupThread();
                    break;
                case 'get_group_threads':
                    $this->getGroupThreads();
                    break;
                case 'get_group_messages':
                    $this->getGroupMessages();
                    break;
                case 'update_location':
                    $this->updateLocation();
                    break;
                case 'get_user_locations':
                    $this->getUserLocations();
                    break;
                case 'get_direct_messages':
                    $this->getDirectMessages();
                    break;
                case 'send_direct_message':
                    $this->sendDirectMessage();
                    break;
                case 'send_message':
                    $this->sendMessage();
                    break;
                case 'delete_message':
                    $this->deleteMessage();
                    break;
                case 'set_last_thread':
                    $this->setLastThread();
                    break;
                case 'request_friend':
                    $this->requestFriend();
                    break;
                case 'accept_friend':
                    $this->acceptFriend();
                    break;
                case 'get_friend_requests':
                    $this->getFriendRequests();
                    break;
                case 'get_friends':
                    $this->getFriends();
                    break;
                case 'toggle_favorite':
                    $this->toggleFavorite();
                    break;
                case 'get_favorites':
                    $this->getFavorites();
                    break;
                case 'check_favorite':
                    $this->checkFavorite();
                    break;
                case 'block_user':
                    $this->blockUser();
                    break;
                case 'unblock_user':
                    $this->unblockUser();
                    break;
                case 'get_blocked_users':
                    $this->getBlockedUsers();
                    break;
                case 'get_my_files':
                    $this->getMyFiles();
                    break;
                case 'search_users':
                    $this->searchUsers();
                    break;
                case 'join_meeting':
                    $this->joinMeeting();
                    break;
                case 'send_signaling':
                    $this->sendSignaling();
                    break;
                case 'get_signaling':
                    $this->getSignaling();
                    break;
                case 'toggle_mute':
                    $this->toggleMute();
                    break;
                case 'get_mute_statuses':
                    $this->getMuteStatuses();
                    break;
                case 'getPinnedMessages':
                case 'get_pinned_messages':
                    $this->getPinnedMessages();
                    break;
                case 'get_online_users':
                    $this->getOnlineUsers();
                    break;
                case 'update_thread':
                    $this->editThread();
                    break;
                case 'get_unread_dm_counts':
                    $this->getUnreadDmCounts();
                    break;
                case 'set_lang':
                    $this->setLang();
                    break;
                default:
                    echo json_encode(['error' => 'Unknown action: ' . $action]);
                    break;
            }
        } catch (\Exception $e) {
            return;
        }
        return;
    }

    private function verifyCsrf(): void
    {
        $token = $this->getPost('csrf_token');
        if (!$token || !hash_equals($this->csrfToken, $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF Token']);
            throw new \Exception('Invalid CSRF Token');
        }
    }

    private function updateProfile(): void
    {
        $this->verifyCsrf();
        $bio = $this->getPost('bio');
        $bannerColor = $this->getPost('banner_color', '#6366f1');
        $status = $this->getPost('status', 'online');
        $removeAvatar = ($this->getPost('remove_avatar', 'false')) === 'true';

        $file = $this->getFile('avatar');
        $hasAvatar = ($file && $file['error'] === UPLOAD_ERR_OK);
        if ($removeAvatar || $hasAvatar) {
            $pStmt = $this->mysqli->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            $result = $pStmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $oldPath = $row['avatar_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../frontend/' . $oldPath)) {
                    unlink(__DIR__ . '/../../frontend/' . $oldPath);
                }
            }
            if ($removeAvatar) {
                $upd = $this->mysqli->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $upd->bind_param("i", $this->userId);
                $upd->execute();
            }
        }
        $social = $this->getPost('social_links');
        $themePref = $this->getPost('theme_preference');
        $keywords = $this->getPost('notification_keywords');
        $profileLayout = $this->getPost('profile_layout', 'classic');
        $removeBanner = ($this->getPost('remove_banner', 'false')) === 'true';

        $file = $this->getFile('banner');
        $hasBanner = ($file && $file['error'] === UPLOAD_ERR_OK);
        if ($removeBanner || $hasBanner) {
            $pStmt = $this->mysqli->prepare("SELECT banner_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            $result = $pStmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $oldPath = $row['banner_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../frontend/' . $oldPath)) {
                    unlink(__DIR__ . '/../../frontend/' . $oldPath);
                }
            }
            if ($removeBanner) {
                $upd = $this->mysqli->prepare("UPDATE users SET banner_url = NULL WHERE id = ?");
                $upd->bind_param("i", $this->userId);
                $upd->execute();
            }
        }
        $stmt = $this->mysqli->prepare("UPDATE users SET bio = ?, banner_color = ?, status = ?, social_links = ?, theme_preference = ?, notification_keywords = ?, profile_layout = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $bio, $bannerColor, $status, $social, $themePref, $keywords, $profileLayout, $this->userId);
        $stmt->execute();
        $this->handleAvatarUpload();
        $this->handleBannerUpload();
        echo json_encode(['success' => true]);
    }

    private function handleAvatarUpload(): void
    {
        $file = $this->getFile('avatar');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $file = $this->getFile('avatar');
            $tmp = $file['tmp_name'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec = new SecurityUtil();
            if ($sec->validateFile($tmp, $ext)) {
                $uuid = $sec->generateUuid();
                $dir = __DIR__ . '/../../frontend/uploads/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                    $path = 'uploads/avatars/' . $uuid . '.' . $ext;
                    $upd = $this->mysqli->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $upd->bind_param("si", $path, $this->userId);
                    $upd->execute();
                }
            }
        }
    }

    private function handleBannerUpload(): void
    {
        $file = $this->getFile('banner');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $file = $this->getFile('banner');
            $tmp = $file['tmp_name'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec = new SecurityUtil();
            if ($sec->validateFile($tmp, $ext)) {
                $uuid = $sec->generateUuid();
                $dir = __DIR__ . '/../../frontend/uploads/banners/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                    $path = 'uploads/banners/' . $uuid . '.' . $ext;
                    $upd = $this->mysqli->prepare("UPDATE users SET banner_url = ? WHERE id = ?");
                    $upd->bind_param("si", $path, $this->userId);
                    $upd->execute();
                }
            }
        }
    }

    private function pushSubscribe()
    {
        $this->verifyCsrf();
        $sub = json_decode($this->getRawInput(), true);
        if ($sub && isset($sub['endpoint'])) {
            $stmt = $this->mysqli->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)");
            $stmt->bind_param("isss", $this->userId, $sub['endpoint'], $sub['keys']['p256dh'], $sub['keys']['auth']);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid subscription data']);
        }
    }

    private function updateStatus()
    {
        $this->verifyCsrf();
        $status = $this->getPost('status', 'online');
        $custom = $this->getPost('custom_status');
        if (in_array($status, ['online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away'])) {
            $stmt = $this->mysqli->prepare("UPDATE users SET status = ?, custom_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $custom, $this->userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        }
    }

    private function getUserStatus()
    {
        $tid = $this->getGet('user_id', 0);
        $stmt = $this->mysqli->prepare("SELECT status, custom_status FROM users WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['status' => 'offline']);
    }

    private function getUserProfile()
    {
        $tid = $this->getGet('user_id', 0);
        $stmt = $this->mysqli->prepare("SELECT id, username, status, custom_status, bio, avatar_url, banner_color, banner_url, profile_layout, social_links FROM users WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['error' => 'Not found']);
    }

    private function getFriendsStatuses()
    {
        $stmt = $this->mysqli->prepare("SELECT u.id, u.status, u.custom_status FROM friends f JOIN users u ON (f.user_id_1 = u.id OR f.user_id_2 = u.id) WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted' AND u.id != ?");
        $stmt->bind_param("iii", $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getThreads()
    {
        echo json_encode($this->mysqli->query("SELECT * FROM threads ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC));
    }

    private function createThread()
    {
        $this->verifyCsrf();
        $name = $this->getPost('name', '');
        $cat = $this->getPost('category', 'General');
        if ($name) {
            $stmt = $this->mysqli->prepare("INSERT INTO threads (name, creator_id, category) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $this->userId, $cat);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        }
    }

    private function editThread()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id', 0);
        $name = $this->getPost('name', '');
        $wh = $this->getPost('discord_webhook_url');
        $cat = $this->getPost('category', 'General');
        $stmt = $this->mysqli->prepare("UPDATE threads SET name = ?, discord_webhook_url = ?, category = ? WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("sssii", $name, $wh, $cat, $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function deleteThread()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id', 0);
        $stmt = $this->mysqli->prepare("DELETE FROM threads WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function toggleReaction()
    {
        $this->verifyCsrf();
        $mid = $this->getPost('message_id', 0);
        $emo = $this->getPost('emoji', '');
        $stmt = $this->mysqli->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
        $stmt->bind_param("iis", $mid, $this->userId, $emo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            $favId = $row['id'];
            $stmt = $this->mysqli->prepare("DELETE FROM message_reactions WHERE id = ?");
            $stmt->bind_param("i", $favId);
            $stmt->execute();
        } else {
            $stmt = $this->mysqli->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $mid, $this->userId, $emo);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
    }

    private function togglePin(): void
    {
        $this->verifyCsrf();
        $mid = (int)$this->getPost('message_id', 0);
        $stmt = $this->mysqli->prepare("UPDATE messages SET is_pinned = NOT is_pinned WHERE id = ?");
        $stmt->bind_param("i", $mid);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function searchMessages()
    {
        $tid = $this->getGet('thread_id');
        $gtid = $this->getGet('group_thread_id');
        $pid = $this->getGet('partner_id');
        $kw = $this->getGet('keyword', '');
        // Simplified search implementation
        $sql = $pid ? "SELECT * FROM direct_messages WHERE 1=1" : "SELECT * FROM messages WHERE 1=1";
        if ($tid) $sql .= " AND thread_id = $tid";
        if ($gtid) $sql .= " AND group_thread_id = $gtid";
        if ($kw) $sql .= " AND content LIKE '%$kw%'";
        echo json_encode($this->mysqli->query($sql)->fetch_all(MYSQLI_ASSOC));
    }

    private function updateTypingStatus()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id');
        $isTyping = ($this->getPost('is_typing', '0')) === '1';
        $stmt = $this->mysqli->prepare("UPDATE users SET typing_thread_id = ?, typing_at = ? WHERE id = ?");
        $tval = $isTyping ? $tid : null;
        $aval = $isTyping ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("ssi", $tval, $aval, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getTypingUsers()
    {
        $tid = $this->getGet('thread_id', '');
        $stmt = $this->mysqli->prepare("SELECT username FROM users WHERE typing_thread_id = ? AND id != ? AND typing_at > (NOW() - INTERVAL 5 SECOND)");
        $stmt->bind_param("si", $tid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function markDmsAsRead()
    {
        $this->verifyCsrf();
        $pid = $this->getPost('partner_id', 0);
        $stmt = $this->mysqli->prepare("UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $pid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function editMessage()
    {
        $this->verifyCsrf();
        $mid = (int)$this->getPost('message_id', 0);
        $did = (int)$this->getPost('dm_id', 0);
        $con = $this->getPost('content', '');

        $stmt = null;
        if ($mid > 0) {
            $stmt = $this->mysqli->prepare("UPDATE messages SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $con, $mid, $this->userId);
        } elseif ($did > 0) {
            $stmt = $this->mysqli->prepare("UPDATE direct_messages SET content = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
            $stmt->bind_param("sii", $con, $did, $this->userId);
        }

        if ($stmt) {
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid message or DM ID']);
        }
    }

    private function getAttachments()
    {
        $tid = (int)$this->getGet('thread_id', 0);
        $pid = (int)$this->getGet('partner_id', 0);

        $stmt = null;
        if ($tid > 0) {
            $stmt = $this->mysqli->prepare("SELECT attachment_path FROM messages WHERE thread_id = ? AND attachment_path IS NOT NULL");
            $stmt->bind_param("i", $tid);
        } else {
            $stmt = $this->mysqli->prepare("SELECT attachment_path FROM direct_messages WHERE (sender_id = ? OR receiver_id = ?) AND attachment_path IS NOT NULL");
            $stmt->bind_param("ii", $this->userId, $this->userId);
        }

        if ($stmt) {
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            echo json_encode([]);
        }
    }

    private function getMessages(): void
    {
        $tid = (int)($this->getGet('thread_id', 0));
        $stmt = $this->mysqli->prepare(
            "SELECT m.*, u.username, u.avatar_url, u.status,
                r.username AS reply_username
            FROM messages m
            JOIN users u ON m.user_id = u.id
            LEFT JOIN users r ON m.reply_to_id = r.id
            WHERE m.thread_id = ?
            ORDER BY m.created_at ASC"
        );
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // リアクションを取得して各メッセージに付加
        if (!empty($messages)) {
            $ids = array_column($messages, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $rStmt = $this->mysqli->prepare(
                "SELECT message_id, user_id, emoji FROM message_reactions WHERE message_id IN ($placeholders)"
            );
            $rStmt->bind_param($types, ...$ids);
            $rStmt->execute();
            $reactions = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $reactMap = [];
            foreach ($reactions as $r) {
                $reactMap[$r['message_id']][] = $r;
            }
            foreach ($messages as &$msg) {
                $msg['reactions'] = $reactMap[$msg['id']] ?? [];
            }
            unset($msg);
        }

        echo json_encode($messages);
    }

    private function getDmPartners()
    {
        $stmt = $this->mysqli->prepare("SELECT DISTINCT u.id, u.username, u.avatar_url FROM users u JOIN direct_messages dm ON (u.id = dm.sender_id OR u.id = dm.receiver_id) WHERE (dm.sender_id = ? OR dm.receiver_id = ?) AND u.id != ?");
        $stmt->bind_param("iii", $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getAllUsers()
    {
        echo json_encode($this->mysqli->query("SELECT id, username, avatar_url FROM users WHERE id != " . $this->userId)->fetch_all(MYSQLI_ASSOC));
    }

    private function createGroupThread()
    {
        $this->verifyCsrf();
        $name = $this->getPost('name', 'Group');
        $pids = json_decode($this->getPost('participant_ids', '[]'), true);
        $stmt = $this->mysqli->prepare("INSERT INTO group_threads (name, creator_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $this->userId);
        $stmt->execute();
        $tid = $stmt->insert_id;
        $this->mysqli->query("INSERT INTO group_thread_participants (thread_id, user_id) VALUES ($tid, $this->userId)");
        foreach ($pids as $p) $this->mysqli->query("INSERT INTO group_thread_participants (thread_id, user_id) VALUES ($tid, $p)");
        echo json_encode(['success' => true, 'id' => $tid]);
    }

    private function getGroupThreads()
    {
        $stmt = $this->mysqli->prepare("SELECT gt.* FROM group_threads gt JOIN group_thread_participants gtp ON gt.id = gtp.thread_id WHERE gtp.user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getGroupMessages()
    {
        $tid = $this->getGet('thread_id', 0);
        $stmt = $this->mysqli->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.user_id = u.id WHERE m.group_thread_id = ? ORDER BY m.created_at ASC");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function updateLocation()
    {
        $lat = $this->getPost('lat', 0);
        $lon = $this->getPost('lon', 0);
        $stmt = $this->mysqli->prepare("INSERT INTO user_locations (user_id, lat, lon) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE lat=VALUES(lat), lon=VALUES(lon)");
        $stmt->bind_param("idd", $this->userId, $lat, $lon);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getUserLocations()
    {
        echo json_encode($this->mysqli->query("SELECT * FROM user_locations")->fetch_all(MYSQLI_ASSOC));
    }

    private function getDirectMessages()
    {
        $pid = $this->getGet('partner_id', 0);
        $stmt = $this->mysqli->prepare("SELECT * FROM direct_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function sendDirectMessage()
    {
        $this->verifyCsrf();
        $rid = (int)$this->getPost('receiver_id', 0);
        $con = $this->getPost('content', '');
        $att = $this->handleFileUpload();
        $stmt = $this->mysqli->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $this->userId, $rid, $con, $att);
        $stmt->execute();
        $msgId = $stmt->insert_id;

        // Get sender name for notification
        $uStmt = $this->mysqli->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->bind_param("i", $this->userId);
        $uStmt->execute();
        $senderName = $uStmt->get_result()->fetch_assoc()['username'] ?? 'User';

        $messageData = [
            'id' => $msgId,
            'sender_id' => $this->userId,
            'username' => $senderName,
            'content' => $con,
            'attachment_path' => $att,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Notify Realtime Server
        notifyRealtimeServer('new_dm', ['receiverId' => $rid, 'message' => $messageData]);

        // Send Push Notification
        sendPushNotification($rid, [
            'title' => 'New DM from ' . $senderName,
            'body' => $con,
            'data' => ['url' => 'index.php?tab=dm', 'senderId' => $this->userId]
        ]);

        echo json_encode(['success' => true]);
    }

    private function sendMessage()
    {
        $this->verifyCsrf();
        $tid = (int)$this->getPost('thread_id');
        $gtid = (int)$this->getPost('group_thread_id');
        $con = $this->getPost('content', '');
        $replyToId = !empty($this->getPost('reply_to_id')) ? (int)$this->getPost('reply_to_id') : null;
        $att = $this->handleFileUpload();
        $stmt = $this->mysqli->prepare(
            "INSERT INTO messages (thread_id, group_thread_id, user_id, content, attachment_path, reply_to_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiissi", $tid, $gtid, $this->userId, $con, $att, $replyToId);
        $stmt->execute();
        $msgId = $stmt->insert_id;

        // Get sender name and thread name
        $uStmt = $this->mysqli->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->bind_param("i", $this->userId);
        $uStmt->execute();
        $senderName = $uStmt->get_result()->fetch_assoc()['username'] ?? 'User';

        $threadName = 'Thread';
        if ($tid > 0) {
            $tStmt = $this->mysqli->prepare("SELECT name FROM threads WHERE id = ?");
            $tStmt->bind_param("i", $tid);
            $tStmt->execute();
            $threadName = $tStmt->get_result()->fetch_assoc()['name'] ?? 'Thread';
        }

        $messageData = [
            'id' => $msgId,
            'threadId' => $tid,
            'groupThreadId' => $gtid,
            'userId' => $this->userId,
            'username' => $senderName,
            'threadName' => $threadName,
            'content' => $con,
            'attachment_path' => $att,
            'reply_to_id' => $replyToId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Notify Realtime Server
        if ($tid > 0) {
            notifyRealtimeServer('new_message', ['threadId' => $tid, 'message' => $messageData]);
        } elseif ($gtid > 0) {
            notifyRealtimeServer('new_group_message', ['groupThreadId' => $gtid, 'message' => $messageData]);
        }

        // For threads, we could send push to all participants, but for now let's just do real-time
        // Or we could send to users who have certain keywords (as mentioned in the code)

        echo json_encode(['success' => true, 'id' => $msgId]);
    }

    private function deleteMessage()
    {
        $this->verifyCsrf();
        $mid = $this->getPost('message_id', 0);
        $this->mysqli->query("DELETE FROM messages WHERE id = $mid AND user_id = $this->userId");
        echo json_encode(['success' => true]);
    }

    private function setLastThread()
    {
        $tid = $this->getGet('thread_id', 1);
        $stmt = $this->mysqli->prepare("UPDATE users SET last_thread_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function requestFriend()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('target_id', 0);
        $this->mysqli->query("INSERT IGNORE INTO friends (user_id_1, user_id_2, status) VALUES ($this->userId, $tid, 'pending')");
        echo json_encode(['success' => true]);
    }

    private function acceptFriend()
    {
        $this->verifyCsrf();
        $rid = $this->getPost('request_id', 0);
        $this->mysqli->query("UPDATE friends SET status = 'accepted' WHERE id = $rid AND user_id_2 = $this->userId");
        echo json_encode(['success' => true]);
    }

    private function getFriendRequests()
    {
        $stmt = $this->mysqli->prepare("SELECT f.id, u.username FROM friends f JOIN users u ON f.user_id_1 = u.id WHERE f.user_id_2 = ? AND f.status = 'pending'");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getFriends()
    {
        $stmt = $this->mysqli->prepare("SELECT u.* FROM users u JOIN friends f ON (u.id = f.user_id_1 OR u.id = f.user_id_2) WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted' AND u.id != ?");
        $stmt->bind_param("iii", $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function toggleFavorite()
    {
        $this->verifyCsrf();
        $tid = (int)($this->getPost('thread_id', 0));
        if ($tid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid thread ID']);
            return;
        }

        $stmt = $this->mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        $stmt->store_result();
        $numRows = $stmt->num_rows;
        $favId = 0;
        $stmt->bind_result($favId);
        $stmt->fetch();
        $stmt->close();

        $is_favorite = false;
        if ($numRows > 0) {
            $delStmt = $this->mysqli->prepare("DELETE FROM favorites WHERE id = ?");
            $delStmt->bind_param("i", $favId);
            $delStmt->execute();
            $delStmt->close();
            $is_favorite = false;
        } else {
            $insStmt = $this->mysqli->prepare("INSERT INTO favorites (user_id, thread_id) VALUES (?, ?)");
            $insStmt->bind_param("ii", $this->userId, $tid);
            $insStmt->execute();
            $insStmt->close();
            $is_favorite = true;
        }
        echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
    }

    private function getFavorites(): void
    {
        $stmt = $this->mysqli->prepare("
        SELECT t.id, t.name, t.category 
        FROM favorites f 
        JOIN threads t ON f.thread_id = t.id 
        WHERE f.user_id = ? 
        ORDER BY f.id DESC
    ");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $id = null;
        $name = null;
        $category = null;
        $stmt->bind_result($id, $name, $category);

        $favorites = [];
        while ($stmt->fetch()) {
            $favorites[] = [
                'id' => $id,
                'name' => $name,
                'category' => $category
            ];
        }
        $stmt->close();
        echo json_encode($favorites);
    }

    private function checkFavorite(): void
    {
        $tid = (int)($this->getGet('thread_id', 0));
        $stmt = $this->mysqli->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        $stmt->store_result();
        $is_favorite = $stmt->num_rows > 0;
        $stmt->close();
        echo json_encode(['is_favorite' => $is_favorite]);
    }

    private function blockUser()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('target_id', 0);
        $this->mysqli->query("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES ($this->userId, $tid)");
        echo json_encode(['success' => true]);
    }

    private function unblockUser()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('target_id', 0);
        $this->mysqli->query("DELETE FROM blocked_users WHERE blocker_id = $this->userId AND blocked_id = $tid");
        echo json_encode(['success' => true]);
    }

    private function getBlockedUsers()
    {
        $stmt = $this->mysqli->prepare("SELECT u.id, u.username FROM blocked_users b JOIN users u ON b.blocked_id = u.id WHERE b.blocker_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getMyFiles()
    {
        $stmt = $this->mysqli->prepare("SELECT attachment_path FROM messages WHERE user_id = ? AND attachment_path IS NOT NULL UNION SELECT attachment_path FROM direct_messages WHERE sender_id = ? AND attachment_path IS NOT NULL");
        $stmt->bind_param("ii", $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function searchUsers(): void
    {
        $q = "%" . ($this->getGet('q', '')) . "%";
        $stmt = $this->mysqli->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ?");
        $stmt->bind_param("si", $q, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function joinMeeting()
    {
        $this->verifyCsrf();
        $tid = $this->getPost('thread_id');
        $pid = $this->getPost('dm_partner_id');
        $name = $tid ? "thread_$tid" : "dm_" . min($this->userId, $pid) . "_" . max($this->userId, $pid);
        $stmt = $this->mysqli->prepare("INSERT IGNORE INTO meeting_rooms (room_name, creator_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true, 'room_name' => $name]);
    }

    private function sendSignaling()
    {
        $this->verifyCsrf();
        $rid = $this->getPost('room_id', 0);
        $receiverId = $this->getPost('receiver_id');
        $type = $this->getPost('type');
        $content = $this->getPost('content');
        $stmt = $this->mysqli->prepare("INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $rid, $this->userId, $receiverId, $type, $content);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getSignaling()
    {
        $stmt = $this->mysqli->prepare("SELECT * FROM signaling WHERE room_id = ? AND receiver_id = ? AND id > ?");
        $roomId = $this->getGet('room_id');
        $lastId = $this->getGet('last_id');
        $stmt->bind_param("iii", $roomId, $this->userId, $lastId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function toggleMute()
    {
        $this->verifyCsrf();
        $type = $this->getPost('target_type');
        $tid = $this->getPost('target_id');
        if ($this->getPost('is_muted') === '1') $this->mysqli->query("INSERT IGNORE INTO user_notification_settings (user_id, target_type, target_id) VALUES ($this->userId, '$type', $tid)");
        else $this->mysqli->query("DELETE FROM user_notification_settings WHERE user_id = $this->userId AND target_type = '$type' AND target_id = $tid");
        echo json_encode(['success' => true]);
    }

    private function getMuteStatuses()
    {
        echo json_encode($this->mysqli->query("SELECT target_type, target_id FROM user_notification_settings WHERE user_id = " . $this->userId)->fetch_all(MYSQLI_ASSOC));
    }

    private function getPinnedMessages()
    {
        $tid = (int)($this->getGet('thread_id', 0));
        if ($tid <= 0) {
            echo json_encode([]);
            return;
        }
        $stmt = $this->mysqli->prepare(
            "SELECT m.*, u.username, u.avatar_url FROM messages m JOIN users u ON m.user_id = u.id WHERE m.thread_id = ? AND m.is_pinned = 1 ORDER BY m.created_at DESC"
        );
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getOnlineUsers()
    {
        $stmt = $this->mysqli->prepare("SELECT id, username, status FROM users WHERE status != 'offline' AND id != ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getUnreadDmCounts()
    {
        $stmt = $this->mysqli->prepare("SELECT sender_id, COUNT(*) as count FROM direct_messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function setLang()
    {
        $this->setSession('lang', $this->getGet('lang', 'ja'));
        echo json_encode(['success' => true]);
    }

    private function handleFileUpload(): ?string
    {
        $file = $this->getFile('attachment');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        require_once __DIR__ . '/../SecurityUtil.php';
        $file = $this->getFile('attachment');
        $tmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $sec = new SecurityUtil();
        if (!$sec->validateFile($tmp, $ext)) return null;
        $uuid = $sec->generateUuid();
        $dir = __DIR__ . '/../../frontend/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $uuid . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $path)) return 'uploads/' . $path;
        return null;
    }

    private function getPost(string $key, $default = null): mixed
    {
        return filter_input(INPUT_POST, $key) ?? $default;
    }

    private function getGet(string $key, $default = null): mixed
    {
        return filter_input(INPUT_GET, $key) ?? $default;
    }

    private function getFile(string $key): ?array
    {
        // @phpstan-ignore-next-line
        $files = $_FILES;
        return $files[$key] ?? null;
    }

    private function getServer(string $key, $default = null): mixed
    {
        return filter_input(INPUT_SERVER, $key) ?? $default;
    }

    private function getSession(string $key, $default = null): mixed
    {
        require_once __DIR__ . '/../Session.php';
        return Session::getInstance()->get($key, $default);
    }

    private function setSession(string $key, $value): void
    {
        require_once __DIR__ . '/../Session.php';
        Session::getInstance()->set($key, $value);
    }

    private function getRawInput()
    {
        return file_get_contents('php://input');
    }
}
