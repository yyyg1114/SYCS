<?php
// backend/discord_config.php
require_once __DIR__ . '/EnvLoader.php';

define('DISCORD_CLIENT_ID', getenv('DISCORD_CLIENT_ID'));
define('DISCORD_CLIENT_SECRET', getenv('DISCORD_CLIENT_SECRET'));
define('DISCORD_BOT_TOKEN', getenv('DISCORD_BOT_TOKEN'));

// Redirect URI will be dynamically generated or set here
// For SYCS, we'll use index.php?api=discord_callback
function getDiscordRedirectUri()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "$protocol://$host$path/login.php?api=discord_callback";
}
