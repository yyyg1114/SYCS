<?php

/**
 * BaseHandler
 * 全ハンドラクラス共通のユーティリティを提供する基底クラス
 */
abstract class BaseHandler
{
    protected $mysqli;
    protected $userId;
    protected $csrfToken;

    private $cachedRawInput = null;

    public function __construct($mysqli, $userId, $csrfToken)
    {
        $this->mysqli    = $mysqli;
        $this->userId    = $userId;
        $this->csrfToken = $csrfToken;
    }

    // ----------------------------------------------------------------
    // CSRF
    // ----------------------------------------------------------------

    protected function verifyCsrf(): void
    {
        $token = $this->getPost('csrf_token');
        if (!$token || !hash_equals($this->csrfToken, $token)) {
            throw new \Exception('Invalid CSRF Token');
        }
    }

    // ----------------------------------------------------------------
    // Input helpers
    // ----------------------------------------------------------------

    protected function getParam(string $key, $default = null): mixed
    {
        $val = filter_input(INPUT_POST, $key);
        if ($val !== null) return $val;

        $json = json_decode($this->getRawInput(), true);
        if (is_array($json) && isset($json[$key])) return $json[$key];

        return filter_input(INPUT_GET, $key) ?? $default;
    }

    protected function getPost(string $key, $default = null): mixed
    {
        $val = filter_input(INPUT_POST, $key);
        if ($val === null) {
            $json = json_decode($this->getRawInput(), true);
            if (is_array($json) && isset($json[$key])) {
                return $json[$key];
            }
        }
        return $val ?? $default;
    }

    protected function getGet(string $key, $default = null): mixed
    {
        return filter_input(INPUT_GET, $key) ?? $default;
    }

    protected function getFile(string $key): ?array
    {
        // @phpstan-ignore-next-line
        return $_FILES[$key] ?? null;
    }

    protected function getServer(string $key, $default = null): mixed
    {
        return filter_input(INPUT_SERVER, $key) ?? $default;
    }

    protected function getSession(string $key, $default = null): mixed
    {
        require_once __DIR__ . '/../Session.php';
        return Session::getInstance()->get($key, $default);
    }

    protected function setSession(string $key, $value): void
    {
        require_once __DIR__ . '/../Session.php';
        Session::getInstance()->set($key, $value);
    }

    protected function getRawInput(): string
    {
        if ($this->cachedRawInput === null) {
            $this->cachedRawInput = (string)file_get_contents('php://input');
        }
        return $this->cachedRawInput;
    }

    // ----------------------------------------------------------------
    // File Upload helpers
    // ----------------------------------------------------------------

    protected function handleFileUpload(): ?string
    {
        $file = $this->getFile('attachment');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        require_once __DIR__ . '/../SecurityUtil.php';
        $file = $this->getFile('attachment');
        $tmp  = $file['tmp_name'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $sec  = new SecurityUtil();
        if (!$sec->validateFile($tmp, $ext)) return null;
        $uuid = $sec->generateUuid();
        $dir  = __DIR__ . '/../../frontend/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $uuid . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $path)) return 'uploads/' . $path;
        return null;
    }

    protected function handleAvatarUpload(): void
    {
        $file = $this->getFile('avatar');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $file = $this->getFile('avatar');
            $tmp  = $file['tmp_name'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec  = new SecurityUtil();
            if ($sec->validateFile($tmp, $ext)) {
                $uuid = $sec->generateUuid();
                $dir  = __DIR__ . '/../../frontend/uploads/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                    $path = 'uploads/avatars/' . $uuid . '.' . $ext;
                    $upd  = $this->mysqli->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $upd->bind_param("si", $path, $this->userId);
                    $upd->execute();
                }
            }
        }
    }

    protected function handleBannerUpload(): void
    {
        $file = $this->getFile('banner');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $file = $this->getFile('banner');
            $tmp  = $file['tmp_name'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec  = new SecurityUtil();
            if ($sec->validateFile($tmp, $ext)) {
                $uuid = $sec->generateUuid();
                $dir  = __DIR__ . '/../../frontend/uploads/banners/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                    $path = 'uploads/banners/' . $uuid . '.' . $ext;
                    $upd  = $this->mysqli->prepare("UPDATE users SET banner_url = ? WHERE id = ?");
                    $upd->bind_param("si", $path, $this->userId);
                    $upd->execute();
                }
            }
        }
    }

    // ----------------------------------------------------------------
    // Authorization & Access Control Helpers
    // ----------------------------------------------------------------

    /**
     * 指定したグループスレッドの参加者かどうかチェック
     */
    public function isGroupParticipant(int $groupThreadId, ?int $userId = null): bool
    {
        $uid = $userId ?? $this->userId;
        if ($groupThreadId <= 0 || !$uid) return false;

        $stmt = $this->mysqli->prepare(
            "SELECT 1 FROM group_thread_participants WHERE thread_id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->bind_param("ii", $groupThreadId, $uid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * 通常スレッドが存在しアクセス可能か確認
     */
    public function canAccessThread(int $threadId): bool
    {
        if ($threadId <= 0) return false;
        $stmt = $this->mysqli->prepare("SELECT id FROM threads WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $threadId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * 指定ユーザーとのDMアクセス権限があるか（フレンド承認済み、または既存DM履歴あり）
     */
    public function canAccessDm(int $partnerId, ?int $userId = null): bool
    {
        $uid = $userId ?? $this->userId;
        if ($partnerId <= 0 || !$uid || $partnerId === $uid) return false;

        $friendStmt = $this->mysqli->prepare(
            "SELECT 1 FROM friends
            WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))
            AND status = 'accepted'
            LIMIT 1"
        );
        $friendStmt->bind_param("iiii", $uid, $partnerId, $partnerId, $uid);
        $friendStmt->execute();
        $friendCheck = $friendStmt->get_result()->fetch_assoc();
        $friendStmt->close();

        if ($friendCheck) return true;

        $dmStmt = $this->mysqli->prepare(
            "SELECT 1 FROM direct_messages
            WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
            LIMIT 1"
        );
        $dmStmt->bind_param("iiii", $uid, $partnerId, $partnerId, $uid);
        $dmStmt->execute();
        $dmExists = $dmStmt->get_result()->fetch_assoc();
        $dmStmt->close();

        return (bool)$dmExists;
    }

    /**
     * メッセージ情報を取得し、現在のユーザーがそのメッセージにアクセス可能か検証
     * @return array|null メッセージデータ（アクセス不可・存在しない場合は null）
     */
    public function canAccessMessage(int $messageId): ?array
    {
        if ($messageId <= 0) return null;

        $stmt = $this->mysqli->prepare(
            "SELECT m.*, t.creator_id AS thread_creator_id, gt.creator_id AS group_creator_id
            FROM messages m
            LEFT JOIN threads t ON m.thread_id = t.id
            LEFT JOIN group_threads gt ON m.group_thread_id = gt.id
            WHERE m.id = ?
            LIMIT 1"
        );
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $msg = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$msg) return null;

        if ($msg['group_thread_id'] !== null) {
            if (!$this->isGroupParticipant((int)$msg['group_thread_id'])) {
                return null;
            }
        } elseif ($msg['thread_id'] !== null) {
            if (!$this->canAccessThread((int)$msg['thread_id'])) {
                return null;
            }
        }

        return $msg;
    }

    /**
     * ミーティングルームが存在し、アクセス資格があるか確認
     */
    public function canAccessMeetingRoom(int $roomId): bool
    {
        if ($roomId <= 0) return false;

        $stmt = $this->mysqli->prepare(
            "SELECT room_name, thread_id, dm_partner_id, creator_id FROM meeting_rooms WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$room) return false;

        if ($room['creator_id'] == $this->userId) return true;

        if ($room['thread_id'] !== null && (int)$room['thread_id'] > 0) {
            return $this->canAccessThread((int)$room['thread_id']);
        }
        if ($room['dm_partner_id'] !== null && (int)$room['dm_partner_id'] > 0) {
            return $this->canAccessDm((int)$room['dm_partner_id']);
        }

        // ルーム名パターン判定 fallback
        if (str_starts_with($room['room_name'], 'thread_')) {
            $tid = (int)str_replace('thread_', '', $room['room_name']);
            return $this->canAccessThread($tid);
        }
        if (str_starts_with($room['room_name'], 'dm_')) {
            $parts = explode('_', $room['room_name']);
            if (count($parts) === 3) {
                $u1 = (int)$parts[1];
                $u2 = (int)$parts[2];
                if ($this->userId === $u1) return $this->canAccessDm($u2);
                if ($this->userId === $u2) return $this->canAccessDm($u1);
            }
        }

        return false;
    }
}

