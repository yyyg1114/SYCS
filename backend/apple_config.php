<?php
// backend/apple_config.php

if (file_exists(__DIR__ . '/apple_config_local.php')) {
    include_once __DIR__ . '/apple_config_local.php';
} else {
    require_once __DIR__ . '/EnvLoader.php';
    if (!defined('APPLE_CLIENT_ID')) define('APPLE_CLIENT_ID', getenv('APPLE_CLIENT_ID'));
    if (!defined('APPLE_TEAM_ID')) define('APPLE_TEAM_ID', getenv('APPLE_TEAM_ID'));
    if (!defined('APPLE_KEY_ID')) define('APPLE_KEY_ID', getenv('APPLE_KEY_ID'));
}

if (!function_exists('getAppleRedirectUri')) {
    function getAppleRedirectUri()
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return "$protocol://$host$path/login.php?api=apple_callback";
    }
}
