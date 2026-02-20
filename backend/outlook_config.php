<?php
// backend/outlook_config.php
require_once __DIR__ . '/EnvLoader.php';

define('OUTLOOK_CLIENT_ID', getenv('OUTLOOK_CLIENT_ID') ?: 'YOUR_OUTLOOK_CLIENT_ID');
define('OUTLOOK_CLIENT_SECRET', getenv('OUTLOOK_CLIENT_SECRET') ?: 'YOUR_OUTLOOK_CLIENT_SECRET');

function getOutlookRedirectUri()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "$protocol://$host$path/login.php?api=outlook_callback";
}
