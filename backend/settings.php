<?php
require 'db.php';
if (!isset($_SESSION['uid'])) exit;

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare(
    "UPDATE users SET theme=? WHERE id=?"
);
$stmt->execute([$data['theme'], $_SESSION['uid']]);
