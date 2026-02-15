<?php
// Secure Session Settings (Must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Default to current domain
        'secure' => isset($_SERVER['HTTPS']), // Only over HTTPS if available
        'httponly' => true, // JavaScript cannot access session cookie
        'samesite' => 'Strict' // Prevent CSRF via cross-site cookies
    ]);
    session_start();
}
