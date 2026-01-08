<?php
$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "SYCS"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
