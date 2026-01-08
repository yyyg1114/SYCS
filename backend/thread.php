<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO threads (title) VALUES (?)");
    $stmt->execute([$_POST['title']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(
        $pdo->query("SELECT * FROM threads")->fetchAll()
    );
}
