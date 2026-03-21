<?php
// backend/db.php

// 環境変数が設定されていない場合のフォールバック（開発用）
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'sycs_chat';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($mysqli->connect_errno) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed", "details" => $mysqli->connect_error]));
}

$mysqli->set_charset("utf8mb4");
