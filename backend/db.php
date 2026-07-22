<?php
// backend/db.php
require_once __DIR__ . '/EnvLoader.php';

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    exit;
}

date_default_timezone_set('Asia/Tokyo');

// 環境変数が未設定の場合は明示的にエラーで停止（フォールバックなし）
$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbName = getenv('DB_NAME');

if ($dbHost === false || $dbUser === false || $dbPass === false || $dbName === false) {
    error_log("FATAL: DB environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME) are not set.");
    http_response_code(500);
    die("Internal Server Error: Database configuration is missing.");
}

$mysqli = new mysqli('p:' . $dbHost, $dbUser, $dbPass, $dbName);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
