<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * GroupHandler
 * グループスレッド作成・一覧・メッセージ取得を担当
 * セキュリティ修正:
 *   - getGroupMessages: スレッドへの参加チェック追加 (IDOR対応)
 *   - createGroupThread: query() を prepare() に変更
 */
class GroupHandler extends BaseHandler
{
    public function createGroupThread(): void
    {
        $this->verifyCsrf();
        $name = $this->getPost('name', 'Group');
        $pids = json_decode($this->getPost('participant_ids', '[]'), true);

        $stmt = $this->mysqli->prepare("INSERT INTO group_threads (name, creator_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $this->userId);
        $stmt->execute();
        $tid = $stmt->insert_id;

        // 自分を参加者として追加
        $ins = $this->mysqli->prepare("INSERT INTO group_thread_participants (thread_id, user_id) VALUES (?, ?)");
        $ins->bind_param("ii", $tid, $this->userId);
        $ins->execute();

        // 指定した参加者を追加
        foreach ($pids as $p) {
            $p = (int)$p;
            if ($p > 0) {
                $ins2 = $this->mysqli->prepare("INSERT INTO group_thread_participants (thread_id, user_id) VALUES (?, ?)");
                $ins2->bind_param("ii", $tid, $p);
                $ins2->execute();
            }
        }

        echo json_encode(['success' => true, 'id' => $tid]);
    }

    public function getGroupThreads(): void
    {
        $stmt = $this->mysqli->prepare(
            "SELECT gt.* FROM group_threads gt
             JOIN group_thread_participants gtp ON gt.id = gtp.thread_id
             WHERE gtp.user_id = ?"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * [SECURITY FIX] 自分が参加しているグループスレッドのメッセージのみ取得 (IDOR対応)
     */
    public function getGroupMessages(): void
    {
        $tid = (int)$this->getGet('thread_id', 0);

        // 参加チェック
        $chk = $this->mysqli->prepare(
            "SELECT 1 FROM group_thread_participants WHERE thread_id = ? AND user_id = ?"
        );
        $chk->bind_param("ii", $tid, $this->userId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $chk->close();

        $stmt = $this->mysqli->prepare(
            "SELECT m.*, u.username, u.avatar_url, u.status,
             r.username AS reply_username
             FROM messages m
             JOIN users u ON m.user_id = u.id
             LEFT JOIN users r ON m.reply_to_id = r.id
             WHERE m.group_thread_id = ?
             ORDER BY m.created_at ASC"
        );
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // リアクションを各メッセージに付加（通常メッセージと同モデルに統一）
        if (!empty($messages)) {
            $ids          = array_column($messages, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types        = str_repeat('i', count($ids));
            $rStmt        = $this->mysqli->prepare(
                "SELECT message_id, user_id, emoji FROM message_reactions WHERE message_id IN ($placeholders)"
            );
            $rStmt->bind_param($types, ...$ids);
            $rStmt->execute();
            $reactions = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $reactMap = [];
            foreach ($reactions as $r) {
                $reactMap[$r['message_id']][] = $r;
            }
            foreach ($messages as &$msg) {
                $msg['reactions'] = $reactMap[$msg['id']] ?? [];
            }
            unset($msg);
        }

        echo json_encode($messages);
    }
}
