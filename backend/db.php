<?php
$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "SYCS_users"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
