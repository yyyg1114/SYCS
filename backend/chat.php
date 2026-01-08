<?php
require 'db.php';
require 'spam_guard.php';

if (!isset($_SESSION['uid'])) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        "INSERT INTO messages (user_id,thread_id,content)
    VALUES (?,?,?)"
    );
    $stmt->execute([
        $_SESSION['uid'],
        $_POST['thread_id'],
        $_POST['content']
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        "SELECT u.username,m.content,m.created_at
    FROM messages m
    JOIN users u ON u.id=m.user_id
    WHERE thread_id=? ORDER BY m.id ASC"
    );
    $stmt->execute([$_GET['thread_id']]);
    echo json_encode($stmt->fetchAll());
}
