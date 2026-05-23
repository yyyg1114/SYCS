<?php
$mysqli = new mysqli('localhost', 'root', '', 'sycs_suchgamer');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("SHOW CREATE TABLE messages");
$row = $res->fetch_row();
echo $row[1] . "\n";
