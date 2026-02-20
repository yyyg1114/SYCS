<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/EnvLoader.php';

date_default_timezone_set('Asia/Tokyo');
$mysqli = new mysqli(
    getenv('DB_HOST') ?: "localhost",
    getenv('DB_USER') ?: "root",
    getenv('DB_PASS') ?: "",
    getenv('DB_NAME') ?: "SYCS_suchgamer"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
