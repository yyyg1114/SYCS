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

    public function handle($action)
    {
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
        exit;
    }

    private function verifyCsrf()
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!$token || !hash_equals($this->csrfToken, $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF Token']);
            exit;
        }
    }

    private function updateProfile()
    {
        $this->verifyCsrf();
        $bio = $_POST['bio'] ?? null;
        $bannerColor = $_POST['banner_color'] ?? '#6366f1';
        $status = $_POST['status'] ?? 'online';
        $removeAvatar = ($_POST['remove_avatar'] ?? 'false') === 'true';
        if ($removeAvatar || (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK)) {
            $pStmt = $this->mysqli->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            if ($row = $pStmt->get_result()->fetch_assoc()) {
                $oldPath = $row['avatar_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../frontend/' . $oldPath)) unlink(__DIR__ . '/../../frontend/' . $oldPath);
            }
            if ($removeAvatar) {
                $upd = $this->mysqli->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $upd->bind_param("i", $this->userId);
                $upd->execute();
            }
        }
        $social = $_POST['social_links'] ?? null;
        $themePref = $_POST['theme_preference'] ?? null;
        $keywords = $_POST['notification_keywords'] ?? null;
        $profileLayout = $_POST['profile_layout'] ?? 'classic';
        $removeBanner = ($_POST['remove_banner'] ?? 'false') === 'true';
        if ($removeBanner || (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK)) {
            $pStmt = $this->mysqli->prepare("SELECT banner_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            if ($row = $pStmt->get_result()->fetch_assoc()) {
                $oldPath = $row['banner_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../frontend/' . $oldPath)) unlink(__DIR__ . '/../../frontend/' . $oldPath);
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

    private function handleAvatarUpload()
    {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $tmp = $_FILES['avatar']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (SecurityUtil::validateFile($tmp, $ext)) {
                $uuid = SecurityUtil::generateUuid();
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

    private function handleBannerUpload()
    {
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $tmp = $_FILES['banner']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
            if (SecurityUtil::validateFile($tmp, $ext)) {
                $uuid = SecurityUtil::generateUuid();
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
        $sub = json_decode(file_get_contents('php://input'), true);
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
        $status = $_POST['status'] ?? 'online';
        $custom = $_POST['custom_status'] ?? null;
        if (in_array($status, ['online', 'busy', 'away', 'offline', 'not_allowed', 'step_out', 'going_away'])) {
            $stmt = $this->mysqli->prepare("UPDATE users SET status = ?, custom_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $custom, $this->userId);
            $stmt->execute();
            echo json_encode(['success' => true]);
        }
    }

    private function getUserStatus()
    {
        $tid = $_GET['user_id'] ?? 0;
        $stmt = $this->mysqli->prepare("SELECT status, custom_status FROM users WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['status' => 'offline']);
    }

    private function getUserProfile()
    {
        $tid = $_GET['user_id'] ?? 0;
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
        $name = $_POST['name'] ?? '';
        $cat = $_POST['category'] ?? 'General';
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
        $tid = $_POST['thread_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $wh = $_POST['discord_webhook_url'] ?? null;
        $cat = $_POST['category'] ?? 'General';
        $stmt = $this->mysqli->prepare("UPDATE threads SET name = ?, discord_webhook_url = ?, category = ? WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("sssii", $name, $wh, $cat, $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function deleteThread()
    {
        $this->verifyCsrf();
        $tid = $_POST['thread_id'] ?? 0;
        $stmt = $this->mysqli->prepare("DELETE FROM threads WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function toggleReaction()
    {
        $this->verifyCsrf();
        $mid = $_POST['message_id'] ?? 0;
        $emo = $_POST['emoji'] ?? '';
        $stmt = $this->mysqli->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
        $stmt->bind_param("iis", $mid, $this->userId, $emo);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $this->mysqli->query("DELETE FROM message_reactions WHERE id = " . $row['id']);
        } else {
            $stmt = $this->mysqli->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $mid, $this->userId, $emo);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
    }

    private function togglePin()
    {
        $this->verifyCsrf();
        $mid = $_POST['message_id'] ?? 0;
        $this->mysqli->query("UPDATE messages SET is_pinned = NOT is_pinned WHERE id = $mid");
        echo json_encode(['success' => true]);
    }

    private function searchMessages()
    {
        $tid = $_GET['thread_id'] ?? null;
        $gtid = $_GET['group_thread_id'] ?? null;
        $pid = $_GET['partner_id'] ?? null;
        $kw = $_GET['keyword'] ?? '';
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
        $tid = $_POST['thread_id'] ?? null;
        $isTyping = ($_POST['is_typing'] ?? '0') === '1';
        $stmt = $this->mysqli->prepare("UPDATE users SET typing_thread_id = ?, typing_at = ? WHERE id = ?");
        $tval = $isTyping ? $tid : null;
        $aval = $isTyping ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("ssi", $tval, $aval, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getTypingUsers()
    {
        $tid = $_GET['thread_id'] ?? '';
        $stmt = $this->mysqli->prepare("SELECT username FROM users WHERE typing_thread_id = ? AND id != ? AND typing_at > (NOW() - INTERVAL 5 SECOND)");
        $stmt->bind_param("si", $tid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function markDmsAsRead()
    {
        $this->verifyCsrf();
        $pid = $_POST['partner_id'] ?? 0;
        $stmt = $this->mysqli->prepare("UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $pid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function editMessage()
    {
        $this->verifyCsrf();
        $mid = $_POST['message_id'] ?? 0;
        $did = $_POST['dm_id'] ?? 0;
        $con = $_POST['content'] ?? '';
        if ($mid) $stmt = $this->mysqli->prepare("UPDATE messages SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
        else $stmt = $this->mysqli->prepare("UPDATE direct_messages SET content = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("sii", $con, $mid ?: $did, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getAttachments()
    {
        $tid = $_GET['thread_id'] ?? null;
        $pid = $_GET['partner_id'] ?? null;
        if ($tid) $stmt = $this->mysqli->prepare("SELECT attachment_path FROM messages WHERE thread_id = ? AND attachment_path IS NOT NULL");
        else $stmt = $this->mysqli->prepare("SELECT attachment_path FROM direct_messages WHERE (sender_id = ? OR receiver_id = ?) AND attachment_path IS NOT NULL");
        $stmt->bind_param("i", $tid ?: $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function getMessages()
    {
        $tid = (int)($_GET['thread_id'] ?? 0);
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
        $name = $_POST['name'] ?? 'Group';
        $pids = json_decode($_POST['participant_ids'] ?? '[]', true);
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
        $tid = $_GET['thread_id'] ?? 0;
        $stmt = $this->mysqli->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.user_id = u.id WHERE m.group_thread_id = ? ORDER BY m.created_at ASC");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function updateLocation()
    {
        $lat = $_POST['lat'] ?? 0;
        $lon = $_POST['lon'] ?? 0;
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
        $pid = $_GET['partner_id'] ?? 0;
        $stmt = $this->mysqli->prepare("SELECT * FROM direct_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt->bind_param("iiii", $this->userId, $pid, $pid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function sendDirectMessage()
    {
        $this->verifyCsrf();
        $rid = $_POST['receiver_id'] ?? 0;
        $con = $_POST['content'] ?? '';
        $att = $this->handleFileUpload();
        $stmt = $this->mysqli->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content, attachment_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $this->userId, $rid, $con, $att);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function sendMessage()
    {
        $this->verifyCsrf();
        $tid = $_POST['thread_id'] ?? null;
        $gtid = $_POST['group_thread_id'] ?? null;
        $con = $_POST['content'] ?? '';
        $replyToId = !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;
        $att = $this->handleFileUpload();
        $stmt = $this->mysqli->prepare(
            "INSERT INTO messages (thread_id, group_thread_id, user_id, content, attachment_path, reply_to_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiissi", $tid, $gtid, $this->userId, $con, $att, $replyToId);
        $stmt->execute();
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    }

    private function deleteMessage()
    {
        $this->verifyCsrf();
        $mid = $_POST['message_id'] ?? 0;
        $this->mysqli->query("DELETE FROM messages WHERE id = $mid AND user_id = $this->userId");
        echo json_encode(['success' => true]);
    }

    private function setLastThread()
    {
        $tid = $_GET['thread_id'] ?? 1;
        $stmt = $this->mysqli->prepare("UPDATE users SET last_thread_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function requestFriend()
    {
        $this->verifyCsrf();
        $tid = $_POST['target_id'] ?? 0;
        $this->mysqli->query("INSERT IGNORE INTO friends (user_id_1, user_id_2, status) VALUES ($this->userId, $tid, 'pending')");
        echo json_encode(['success' => true]);
    }

    private function acceptFriend()
    {
        $this->verifyCsrf();
        $rid = $_POST['request_id'] ?? 0;
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
        $tid = $_POST['thread_id'] ?? 0;
        $stmt = $this->mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) $this->mysqli->query("DELETE FROM favorites WHERE id = " . $row['id']);
        else $this->mysqli->query("INSERT INTO favorites (user_id, thread_id) VALUES ($this->userId, $tid)");
        echo json_encode(['success' => true]);
    }

    private function getFavorites()
    {
        $stmt = $this->mysqli->prepare("SELECT t.* FROM favorites f JOIN threads t ON f.thread_id = t.id WHERE f.user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function checkFavorite()
    {
        $tid = $_GET['thread_id'] ?? 0;
        $stmt = $this->mysqli->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        echo json_encode(['is_favorite' => $stmt->get_result()->num_rows > 0]);
    }

    private function blockUser()
    {
        $this->verifyCsrf();
        $tid = $_POST['target_id'] ?? 0;
        $this->mysqli->query("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES ($this->userId, $tid)");
        echo json_encode(['success' => true]);
    }

    private function unblockUser()
    {
        $this->verifyCsrf();
        $tid = $_POST['target_id'] ?? 0;
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

    private function searchUsers()
    {
        $q = "%" . ($_GET['q'] ?? '') . "%";
        $stmt = $this->mysqli->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ?");
        $stmt->bind_param("si", $q, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function joinMeeting()
    {
        $this->verifyCsrf();
        $tid = $_POST['thread_id'] ?? null;
        $pid = $_POST['dm_partner_id'] ?? null;
        $name = $tid ? "thread_$tid" : "dm_" . min($this->userId, $pid) . "_" . max($this->userId, $pid);
        $stmt = $this->mysqli->prepare("INSERT IGNORE INTO meeting_rooms (room_name, creator_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true, 'room_name' => $name]);
    }

    private function sendSignaling()
    {
        $this->verifyCsrf();
        $rid = $_POST['room_id'] ?? 0;
        $stmt = $this->mysqli->prepare("INSERT INTO signaling (room_id, sender_id, receiver_id, type, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $rid, $this->userId, $_POST['receiver_id'], $_POST['type'], $_POST['content']);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    private function getSignaling()
    {
        $stmt = $this->mysqli->prepare("SELECT * FROM signaling WHERE room_id = ? AND receiver_id = ? AND id > ?");
        $stmt->bind_param("iii", $_GET['room_id'], $this->userId, $_GET['last_id']);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function toggleMute()
    {
        $this->verifyCsrf();
        $type = $_POST['target_type'];
        $tid = $_POST['target_id'];
        if ($_POST['is_muted'] === '1') $this->mysqli->query("INSERT IGNORE INTO user_notification_settings (user_id, target_type, target_id) VALUES ($this->userId, '$type', $tid)");
        else $this->mysqli->query("DELETE FROM user_notification_settings WHERE user_id = $this->userId AND target_type = '$type' AND target_id = $tid");
        echo json_encode(['success' => true]);
    }

    private function getMuteStatuses()
    {
        echo json_encode($this->mysqli->query("SELECT target_type, target_id FROM user_notification_settings WHERE user_id = " . $this->userId)->fetch_all(MYSQLI_ASSOC));
    }

    private function getPinnedMessages()
    {
        $tid = (int)($_GET['thread_id'] ?? 0);
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
        echo json_encode($this->mysqli->query("SELECT id, username, status FROM users WHERE status != 'offline' AND id != " . $this->userId)->fetch_all(MYSQLI_ASSOC));
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
        $_SESSION['lang'] = $_GET['lang'] ?? 'ja';
        echo json_encode(['success' => true]);
    }

    private function handleFileUpload()
    {
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) return null;
        require_once __DIR__ . '/../SecurityUtil.php';
        $tmp = $_FILES['attachment']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (!SecurityUtil::validateFile($tmp, $ext)) return null;
        $uuid = SecurityUtil::generateUuid();
        $dir = __DIR__ . '/../../frontend/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $uuid . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $path)) return 'uploads/' . $path;
        return null;
    }
}
