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
}
