<?php
// backend/discord_config.php

define('DISCORD_CLIENT_ID', '1313813135487930368');
define('DISCORD_CLIENT_SECRET', 'T_Bb2xIVcNooySoDpTNh-VqceyAiZtTq');
define('DISCORD_BOT_TOKEN', 'MTMxMzgxMzEzNTQ4NzkzMDM2OA.GYFT7w.Nsg78LVvK67wvxVpcR1hi1oIUwVqG1l6v71DeA');

// Redirect URI will be dynamically generated or set here
// For SYCS, we'll use index.php?api=discord_callback
function getDiscordRedirectUri()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "$protocol://$host$path/login.php?api=discord_callback";
}
