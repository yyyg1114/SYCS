<?php
session_start();
require 'db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
if ($stmt->execute([$username, $email, $password])) {
    $_SESSION['user_id'] = $pdo->lastInsertId();
    header("Location: ../frontend/index.php");
} else {
    echo "登録失敗";
}
