<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($data['type'] === 'signup') {
    $stmt = $pdo->prepare(
        "INSERT INTO users (username,email,password)
    VALUES (?,?,?)"
    );
    $stmt->execute([
        $data['username'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT)
    ]);
    echo json_encode(["ok" => true]);
}

if ($data['type'] === 'login') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($data['password'], $user['password'])) {
        $_SESSION['uid'] = $user['id'];
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false]);
    }
}
