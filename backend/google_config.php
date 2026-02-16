<?php
// backend/google_config.php

define('GOOGLE_CLIENT_ID', '152940732317-aohoelvdg1k122p7q14h4u2dka9bsqur.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

function getGoogleRedirectUri()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "$protocol://$host$path/login.php?api=google_callback";
}
