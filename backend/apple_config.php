<?php
// backend/apple_config.php

define('APPLE_CLIENT_ID', 'YOUR_APPLE_CLIENT_ID');
define('APPLE_TEAM_ID', 'YOUR_APPLE_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_APPLE_KEY_ID');
define('APPLE_PRIVATE_KEY_PATH', __DIR__ . '/apple_private_key.p8');

function getAppleRedirectUri()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "$protocol://$host$path/login.php?api=apple_callback";
}
