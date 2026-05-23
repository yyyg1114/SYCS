<?php
$mysqli = new mysqli('localhost', 'root', '', 'sycs_suchgamer');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}
$res = $mysqli->query('SELECT id, name FROM threads');
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['name'] . PHP_EOL;
}
