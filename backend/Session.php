<?php

/**
 * Session handling utility to abstract super-global $_SESSION access.
 */
class Session
{
    private static $instance = null;

    /**
     * Get the singleton instance of Session
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start the session if not already started.
     */
    public function start(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->start()) {
            return $_SESSION[$key] ?? $default;
        }
        return $default;
    }

    /**
     * Set a value in the session.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        if ($this->start()) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Check if a key exists in the session.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if ($this->start()) {
            return isset($_SESSION[$key]);
        }
        return false;
    }

    /**
     * Remove a value from the session.
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        if ($this->start() && isset($_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session data.
     */
    public function clear(): void
    {
        if ($this->start() && isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     * Check if the session is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
