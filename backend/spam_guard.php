<?php
if (!isset($_SESSION['last_post'])) {
    $_SESSION['last_post'] = 0;
}

if (time() - $_SESSION['last_post'] < 3) {
    http_response_code(429);
    exit("Too fast");
}

$_SESSION['last_post'] = time();
