<?php
$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "SYCS_suchgamer"
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}
