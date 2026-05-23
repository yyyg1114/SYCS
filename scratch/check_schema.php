<?php
$mysqli = new mysqli('localhost', 'root', '', 'sycs_suchgamer');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$res = $mysqli->query('DESCRIBE messages');
print_r($res->fetch_all(MYSQLI_ASSOC));
