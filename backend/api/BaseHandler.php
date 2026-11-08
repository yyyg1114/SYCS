<?php

require_once __DIR__ . '/../FileRateLimiter.php';

class ApiRequestException extends \RuntimeException
{
    private $httpStatus;

    public function __construct(string $message, int $httpStatus)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}

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
        if (!is_string($token) || $token === '' || !is_string($this->csrfToken) || !hash_equals($this->csrfToken, $token)) {
            throw new \Exception('Invalid CSRF Token');
        }
    }

    protected function enforceRateLimit(string $scope, int $maxRequests, int $windowSeconds = 60): bool
    {
        $limiter = new FileRateLimiter();
        $result = $limiter->checkUserRateLimit((int)$this->userId, $maxRequests, $windowSeconds, $scope);
        if ($result['allowed']) {
            return true;
        }

        $retryAfter = max(1, (int)$result['retry_after']);
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        echo json_encode([
            'success' => false,
            'error' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ]);
        return false;
    }

    protected function validateMessageContent($content, bool $allowEmpty = false): bool
    {
        if (!is_string($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message content must be text']);
            return false;
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($content, 'UTF-8')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message content must be valid UTF-8']);
            return false;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
        if ($length > 5000 || (!$allowEmpty && trim($content) === '')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message must contain 1 to 5000 characters']);
            return false;
        }

        return true;
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
        $file = $this->getValidatedUpload('attachment');
        if ($file === null) {
            return null;
        }
        require_once __DIR__ . '/../SecurityUtil.php';
        $tmp  = $file['tmp_name'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $sec  = new SecurityUtil();
        $uuid = $sec->generateUuid();
        $dir  = __DIR__ . '/../../frontend/uploads/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create upload directory');
        }
        $path = $uuid . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . $path)) {
            throw new \RuntimeException('Unable to move uploaded attachment');
        }
        return 'uploads/' . $path;
    }

    protected function handleAvatarUpload(): void
    {
        $file = $this->getValidatedUpload('avatar');
        if ($file !== null) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $tmp  = $file['tmp_name'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec  = new SecurityUtil();
            $uuid = $sec->generateUuid();
            $dir  = __DIR__ . '/../../frontend/uploads/avatars/';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create avatar upload directory');
            }
            if (!move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                throw new \RuntimeException('Unable to move uploaded avatar');
            }
            $path = 'uploads/avatars/' . $uuid . '.' . $ext;
            $upd  = $this->mysqli->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $upd->bind_param("si", $path, $this->userId);
            $upd->execute();
        }
    }

    protected function handleBannerUpload(): void
    {
        $file = $this->getValidatedUpload('banner');
        if ($file !== null) {
            require_once __DIR__ . '/../SecurityUtil.php';
            $tmp  = $file['tmp_name'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sec  = new SecurityUtil();
            $uuid = $sec->generateUuid();
            $dir  = __DIR__ . '/../../frontend/uploads/banners/';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create banner upload directory');
            }
            if (!move_uploaded_file($tmp, $dir . $uuid . '.' . $ext)) {
                throw new \RuntimeException('Unable to move uploaded banner');
            }
            $path = 'uploads/banners/' . $uuid . '.' . $ext;
            $upd  = $this->mysqli->prepare("UPDATE users SET banner_url = ? WHERE id = ?");
            $upd->bind_param("si", $path, $this->userId);
            $upd->execute();
        }
    }

    private function getValidatedUpload(string $key): ?array
    {
        $file = $this->getFile($key);
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > 10 * 1024 * 1024 + 65536) {
                throw new ApiRequestException('Upload exceeds the 10 MB limit', 413);
            }
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $status = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 413 : 400;
            throw new ApiRequestException('Upload failed or exceeds the 10 MB limit', $status);
        }

        $path = $file['tmp_name'] ?? '';
        $size = is_file($path) ? filesize($path) : false;
        if ($size === false || $size > 10 * 1024 * 1024 || (int)($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new ApiRequestException('Upload exceeds the 10 MB limit', 413);
        }

        require_once __DIR__ . '/../SecurityUtil.php';
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $security = new SecurityUtil();
        if (!$security->validateFile($path, $extension)) {
            throw new ApiRequestException('Unsupported or invalid upload file', 400);
        }
        if ($extension === 'svg') {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException('Unable to read uploaded SVG');
            }
            $sanitized = SecurityUtil::sanitizeSVG($content);
            if ($sanitized === '') {
                throw new ApiRequestException('Invalid SVG upload', 400);
            }
            if (file_put_contents($path, $sanitized, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to sanitize uploaded SVG');
            }
        }

        return $file;
    }

    protected function validateUpload(string $key): void
    {
        $this->getValidatedUpload($key);
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

        $blockedStmt = $this->mysqli->prepare(
            "SELECT 1 FROM blocked_users
            WHERE (blocker_id = ? AND blocked_id = ?)
               OR (blocker_id = ? AND blocked_id = ?)
            LIMIT 1"
        );
        $blockedStmt->bind_param("iiii", $uid, $partnerId, $partnerId, $uid);
        $blockedStmt->execute();
        $isBlocked = $blockedStmt->get_result()->fetch_assoc();
        $blockedStmt->close();
        if ($isBlocked) return false;

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
    public function canAccessMeetingRoom(int $roomId, ?int $userId = null): bool
    {
        $uid = $userId ?? $this->userId;

        if ($roomId <= 0 || !$uid) {
            return false;
        }

        $stmt = $this->mysqli->prepare(
            "SELECT
            id,
            room_name,
            room_type,
            thread_id,
            group_thread_id,
            dm_user_1,
            dm_user_2,
            creator_id
        FROM meeting_rooms
        WHERE id = ?
        LIMIT 1"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$room) {
            return false;
        }

        // 作成者は常にアクセス可能
        if ((int)$room['creator_id'] === $uid) {
            return true;
        }

        // 現在参加中の参加者ならアクセス可能
        $participantStmt = $this->mysqli->prepare(
            "SELECT 1
        FROM meeting_participants
        WHERE room_id = ?
        AND user_id = ?
        AND left_at IS NULL
        LIMIT 1"
        );

        if ($participantStmt) {
            $participantStmt->bind_param("ii", $roomId, $uid);
            $participantStmt->execute();
            $participant = $participantStmt->get_result()->fetch_assoc();
            $participantStmt->close();

            if ($participant) {
                return true;
            }
        }

        // まだ参加者登録されていない場合は、
        // ルーム種別ごとの元のリソース権限を確認する
        switch ($room['room_type']) {
            case 'thread':
                return $room['thread_id']
                    ? $this->canAccessThread((int)$room['thread_id'])
                    : false;

            case 'group':
                return $room['group_thread_id']
                    ? $this->isGroupParticipant(
                        (int)$room['group_thread_id'],
                        $uid
                    )
                    : false;

            case 'dm':
                $u1 = (int)$room['dm_user_1'];
                $u2 = (int)$room['dm_user_2'];

                if ($uid === $u1) {
                    return $u2 > 0 && $this->canAccessDm($u2, $uid);
                }

                if ($uid === $u2) {
                    return $u1 > 0 && $this->canAccessDm($u1, $uid);
                }

                return false;

            case 'instant':
                // instant room は creator または明示的に参加登録されたユーザーのみ
                return false;

            default:
                return false;
        }
    }
}
