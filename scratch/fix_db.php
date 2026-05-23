<?php
$mysqli = new mysqli('localhost', 'root', '', 'sycs_suchgamer');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Modify thread_id to be nullable
$res = $mysqli->query("ALTER TABLE messages MODIFY COLUMN thread_id INT NULL");
if ($res) {
    echo "Success: messages.thread_id is now nullable.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
