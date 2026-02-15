<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    exit;
}
date_default_timezone_set('Asia/Tokyo');
$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "SYCS_suchgamer"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
