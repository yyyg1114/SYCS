<?php
// backend/google_config.php

if (file_exists(__DIR__ . '/google_config_local.php')) {
    include_once __DIR__ . '/google_config_local.php';
} else {
    require_once __DIR__ . '/EnvLoader.php';
    if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
    if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
}

if (!function_exists('getGoogleRedirectUri')) {
    function getGoogleRedirectUri()
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return "$protocol://$host$path/login.php?api=google_callback";
    }
}
