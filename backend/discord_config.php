<?php
// backend/discord_config.php

if (file_exists(__DIR__ . '/discord_config_local.php')) {
    include_once __DIR__ . '/discord_config_local.php';
} else {
    require_once __DIR__ . '/EnvLoader.php';
    if (!defined('DISCORD_CLIENT_ID')) define('DISCORD_CLIENT_ID', getenv('DISCORD_CLIENT_ID'));
    if (!defined('DISCORD_CLIENT_SECRET')) define('DISCORD_CLIENT_SECRET', getenv('DISCORD_CLIENT_SECRET'));
    if (!defined('DISCORD_BOT_TOKEN')) define('DISCORD_BOT_TOKEN', getenv('DISCORD_BOT_TOKEN'));
}

if (!function_exists('getDiscordRedirectUri')) {
    function getDiscordRedirectUri()
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return "$protocol://$host$path/login.php?api=discord_callback";
    }
}
