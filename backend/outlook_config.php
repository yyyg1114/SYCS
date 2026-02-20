<?php
// backend/outlook_config.php

if (file_exists(__DIR__ . '/outlook_config_local.php')) {
    include_once __DIR__ . '/outlook_config_local.php';
} else {
    require_once __DIR__ . '/EnvLoader.php';
    if (!defined('OUTLOOK_CLIENT_ID')) define('OUTLOOK_CLIENT_ID', getenv('OUTLOOK_CLIENT_ID'));
    if (!defined('OUTLOOK_CLIENT_SECRET')) define('OUTLOOK_CLIENT_SECRET', getenv('OUTLOOK_CLIENT_SECRET'));
}

if (!function_exists('getOutlookRedirectUri')) {
    function getOutlookRedirectUri()
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return "$protocol://$host$path/login.php?api=outlook_callback";
    }
}
