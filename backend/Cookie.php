<?php

/**
 * Cookie handling utility to abstract super-global $_COOKIE access.
 */
class Cookie
{
    private static $instance = null;

    /**
     * Get the singleton instance of Cookie
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a value from the cookie.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Set a value in the cookie.
     *
     * @param string $key
     * @param mixed $value
     * @param int $expiry Expiry time in seconds from now. Default is 30 days.
     * @param string $path Cookie path. Default is "/".
     * @param string $domain Cookie domain.
     * @param bool $secure
     * @param bool $httponly
     */
    public function set(string $key, $value, int $expiry = 2592000, string $path = "/", string $domain = "", bool $secure = false, bool $httponly = false): void
    {
        if (!headers_sent()) {
            setcookie($key, $value, time() + $expiry, $path, $domain, $secure, $httponly);
            // Update $_COOKIE super-global so it's available in the current request if needed
            $_COOKIE[$key] = $value;
        }
    }

    /**
     * Remove a value from the cookie.
     *
     * @param string $key
     * @param string $path
     */
    public function delete(string $key, string $path = "/"): void
    {
        if (!headers_sent()) {
            setcookie($key, '', time() - 3600, $path);
            if (isset($_COOKIE[$key])) {
                unset($_COOKIE[$key]);
            }
        }
    }

    /**
     * Check if a key exists in the cookie.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_COOKIE[$key]);
    }
}
