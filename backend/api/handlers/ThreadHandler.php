<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * ThreadHandler
 * スレッド CRUD・お気に入り・最終スレッド記録を担当
 */
class ThreadHandler extends BaseHandler
{
    public function getThreads(): void
    {
        $stmt = $this->mysqli->prepare("SELECT * FROM threads ORDER BY created_at ASC");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function createThread(): void
    {
        $this->verifyCsrf();
        $name = $this->getPost('name', '');
        $cat  = $this->getPost('category', 'General');
        if ($name) {
            $stmt = $this->mysqli->prepare("INSERT INTO threads (name, creator_id, category) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $this->userId, $cat);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        }
    }

    public function editThread(): void
    {
        $this->verifyCsrf();
        $tid  = $this->getParam('thread_id', 0);
        $name = $this->getPost('name', '');
        $wh   = $this->getPost('discord_webhook_url');
        $cat  = $this->getPost('category', 'General');
        $stmt = $this->mysqli->prepare("UPDATE threads SET name = ?, discord_webhook_url = ?, category = ? WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("sssii", $name, $wh, $cat, $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function deleteThread(): void
    {
        $this->verifyCsrf();
        $tid  = $this->getParam('thread_id') ?? $this->getParam('id', 0);
        $stmt = $this->mysqli->prepare("DELETE FROM threads WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function setLastThread(): void
    {
        $tid  = $this->getGet('thread_id', 1);
        $stmt = $this->mysqli->prepare("UPDATE users SET last_thread_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $tid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function toggleFavorite(): void
    {
        $this->verifyCsrf();
        $tid = (int)($this->getParam('thread_id', 0));
        if ($tid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid thread ID']);
            return;
        }

        $stmt = $this->mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        $stmt->store_result();
        $numRows = $stmt->num_rows;
        $favId   = 0;
        $stmt->bind_result($favId);
        $stmt->fetch();
        $stmt->close();

        if ($numRows > 0) {
            $delStmt = $this->mysqli->prepare("DELETE FROM favorites WHERE id = ?");
            $delStmt->bind_param("i", $favId);
            $delStmt->execute();
            $delStmt->close();
            echo json_encode(['success' => true, 'is_favorite' => false]);
            return;
        }

        $insStmt = $this->mysqli->prepare("INSERT INTO favorites (user_id, thread_id) VALUES (?, ?)");
        $insStmt->bind_param("ii", $this->userId, $tid);
        $insStmt->execute();
        $insStmt->close();
        echo json_encode(['success' => true, 'is_favorite' => true]);
    }

    public function getFavorites(): void
    {
        $stmt = $this->mysqli->prepare("
SELECT t.id, t.name, t.category
FROM favorites f
JOIN threads t ON f.thread_id = t.id
WHERE f.user_id = ?
ORDER BY f.id DESC
");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $id = null;
        $name = null;
        $category = null;
        $stmt->bind_result($id, $name, $category);

        $favorites = [];
        while ($stmt->fetch()) {
            $favorites[] = ['id' => $id, 'name' => $name, 'category' => $category];
        }
        $stmt->close();
        echo json_encode($favorites);
    }

    public function checkFavorite(): void
    {
        $tid = (int)($this->getGet('thread_id', 0));
        $stmt = $this->mysqli->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $this->userId, $tid);
        $stmt->execute();
        $stmt->store_result();
        $isFavorite = $stmt->num_rows > 0;
        $stmt->close();
        echo json_encode(['is_favorite' => $isFavorite]);
    }
}
