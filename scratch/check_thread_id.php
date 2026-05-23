<?php
$mysqli = new mysqli('localhost', 'root', '', 'sycs_suchgamer');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("DESCRIBE messages");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] === 'thread_id') {
        print_r($row);
    }
}
