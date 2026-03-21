<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $keyword = $_GET['keyword'] ?? '';

    if (trim($keyword) === '') {
        echo json_encode(["success" => true, "results" => []]);
        exit;
    }

    $searchPattern = '%' . $keyword . '%';

    $stmt = $mysqli->prepare("
    SELECT m.id, m.content, m.created_at, m.thread_id, 
            u.username as author_name, t.title as thread_title
    FROM messages m
    JOIN users u ON m.user_id = u.id
    JOIN threads t ON m.thread_id = t.id
    WHERE m.content LIKE ?
    ORDER BY m.created_at DESC
    LIMIT 50
");
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode(["success" => true, "results" => $results]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
