<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * UserHandler
 * ユーザープロフィール・ステータス・フレンドステータス・検索などを担当
 */
class UserHandler extends BaseHandler
{
    public function updateProfile(): void
    {
        $this->verifyCsrf();
        $bio          = $this->getPost('bio');
        $bannerColor  = $this->getPost('banner_color', '#6366f1');
        $status       = $this->getPost('status', 'online');
        $removeAvatar = ($this->getPost('remove_avatar', 'false')) === 'true';

        $file      = $this->getFile('avatar');
        $hasAvatar = ($file && $file['error'] === UPLOAD_ERR_OK);
        if ($removeAvatar || $hasAvatar) {
            $pStmt = $this->mysqli->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            $row = $pStmt->get_result()->fetch_assoc();
            if ($row) {
                $oldPath = $row['avatar_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../../frontend/' . $oldPath)) {
                    unlink(__DIR__ . '/../../../frontend/' . $oldPath);
                }
            }
            if ($removeAvatar) {
                $upd = $this->mysqli->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $upd->bind_param("i", $this->userId);
                $upd->execute();
            }
        }

        $social        = $this->getPost('social_links');
        $themePref     = $this->getPost('theme_preference');
        $keywords      = $this->getPost('notification_keywords');
        $profileLayout = $this->getPost('profile_layout', 'classic');
        $removeBanner  = ($this->getPost('remove_banner', 'false')) === 'true';

        $file      = $this->getFile('banner');
        $hasBanner = ($file && $file['error'] === UPLOAD_ERR_OK);
        if ($removeBanner || $hasBanner) {
            $pStmt = $this->mysqli->prepare("SELECT banner_url FROM users WHERE id = ?");
            $pStmt->bind_param("i", $this->userId);
            $pStmt->execute();
            $row = $pStmt->get_result()->fetch_assoc();
            if ($row) {
                $oldPath = $row['banner_url'];
                if ($oldPath && file_exists(__DIR__ . '/../../../frontend/' . $oldPath)) {
                    unlink(__DIR__ . '/../../../frontend/' . $oldPath);
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

    public function pushSubscribe(): void
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

    public function updateStatus(): void
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

    public function getUserStatus(): void
    {
        $tid  = $this->getGet('user_id', 0);
        $stmt = $this->mysqli->prepare("SELECT status, custom_status FROM users WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['status' => 'offline']);
    }

    public function getUserProfile(): void
    {
        $tid  = $this->getGet('user_id', 0);
        $stmt = $this->mysqli->prepare("SELECT id, username, status, custom_status, bio, avatar_url, banner_color, banner_url, profile_layout, social_links FROM users WHERE id = ?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['error' => 'Not found']);
    }

    public function getFriendsStatuses(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT u.id, u.status, u.custom_status
        FROM friends f
        JOIN users u ON f.user_id_2 = u.id
        WHERE f.user_id_1 = ? AND f.status = 'accepted' AND u.id != ?
        UNION
        SELECT u.id, u.status, u.custom_status
        FROM friends f
        JOIN users u ON f.user_id_1 = u.id
        WHERE f.user_id_2 = ? AND f.status = 'accepted' AND u.id != ?"
        );
        $stmt->bind_param("iiii", $this->userId, $this->userId, $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getAllUsers(): void
    {
        $stmt = $this->mysqli->prepare("SELECT id, username, avatar_url FROM users WHERE id != ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function searchUsers(): void
    {
        $q    = "%" . ($this->getGet('q', '')) . "%";
        $stmt = $this->mysqli->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ?");
        $stmt->bind_param("si", $q, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getOnlineUsers(): void
    {
        $stmt = $this->mysqli->prepare("SELECT id, username, status, avatar_url FROM users WHERE status != 'offline' AND id != ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getMyFiles(): void
    {
        $stmt = $this->mysqli->prepare("SELECT attachment_path FROM messages WHERE user_id = ? AND attachment_path IS NOT NULL UNION SELECT attachment_path FROM direct_messages WHERE sender_id = ? AND attachment_path IS NOT NULL");
        $stmt->bind_param("ii", $this->userId, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function toggleMute(): void
    {
        $this->verifyCsrf();
        $type    = $this->getPost('target_type');
        $tid     = (int)$this->getPost('target_id');
        $isMuted = $this->getPost('is_muted') === '1';

        if ($isMuted) {
            $stmt = $this->mysqli->prepare("INSERT IGNORE INTO user_notification_settings (user_id, target_type, target_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $this->userId, $type, $tid);
        } else {
            $stmt = $this->mysqli->prepare("DELETE FROM user_notification_settings WHERE user_id = ? AND target_type = ? AND target_id = ?");
            $stmt->bind_param("isi", $this->userId, $type, $tid);
        }
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getMuteStatuses(): void
    {
        $stmt = $this->mysqli->prepare("SELECT target_type, target_id FROM user_notification_settings WHERE user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function setLang(): void
    {
        $this->setSession('lang', $this->getGet('lang', 'ja'));
        echo json_encode(['success' => true]);
    }
}
