<?php
$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "tac_ops2"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
