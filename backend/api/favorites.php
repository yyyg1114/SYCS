<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $currentUser['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get favorite threads
    $stmt = $mysqli->prepare("
        SELECT t.*, 1 as is_favorite
        FROM favorites f
        JOIN threads t ON f.thread_id = t.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $favThreads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get favorite DM users
    $stmt = $mysqli->prepare("
        SELECT u.id, u.username, u.status, u.custom_status, u.avatar_url, 1 as is_favorite
        FROM favorites f
        JOIN users u ON f.dm_user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $favDMs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'threads' => $favThreads, 'dms' => $favDMs]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $threadId = $input['thread_id'] ?? null;
    $dmUserId = $input['dm_user_id'] ?? null;

    if (!$threadId && !$dmUserId) {
        echo json_encode(['success' => false, 'error' => 'Target required']);
        exit;
    }

    if ($threadId) {
        // Toggle thread favorite
        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $userId, $threadId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND thread_id = ?");
            $stmt->bind_param("ii", $userId, $threadId);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO favorites (user_id, thread_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $threadId);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    } else {
        // Toggle DM user favorite
        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND dm_user_id = ?");
        $stmt->bind_param("ii", $userId, $dmUserId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND dm_user_id = ?");
            $stmt->bind_param("ii", $userId, $dmUserId);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO favorites (user_id, dm_user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $dmUserId);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    }
}
?>
