<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * MessageHandler
 * スレッド・グループメッセージ関連を担当
 * セキュリティ修正:
 *   - searchMessages: $kw を prepared statement でバインド
 *   - deleteMessage: query() から prepare() に変更
 *   - togglePin: message の thread_id 所有チェック追加 (IDOR対応)
 */
class MessageHandler extends BaseHandler
{
    public function getMessages(): void
    {
        if (function_exists('db_cleanup_expired')) {
            db_cleanup_expired($this->mysqli);
        }
        $tid  = (int)($this->getGet('thread_id', 0));
        $stmt = $this->mysqli->prepare(
            "SELECT m.*, u.username, u.avatar_url, u.status,
        r.username AS reply_username
    FROM messages m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN users r ON m.reply_to_id = r.id
    WHERE m.thread_id = ?
    ORDER BY m.created_at ASC"
        );
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // リアクションを各メッセージに付加
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

    public function sendMessage(): void
    {
        $this->verifyCsrf();
        if (function_exists('db_cleanup_expired')) {
            db_cleanup_expired($this->mysqli);
        }
        $rawTid   = $this->getParam('thread_id');
        $rawGtid  = $this->getParam('group_thread_id');
        $con      = $this->getPost('content', '');
        $replyToId = !empty($this->getPost('reply_to_id')) ? (int)$this->getPost('reply_to_id') : null;
        $att      = $this->handleFileUpload();

        $tidVal  = ($rawTid  && (int)$rawTid  > 0) ? (int)$rawTid  : null;
        $gtidVal = ($rawGtid && (int)$rawGtid > 0) ? (int)$rawGtid : null;

        $stmt = $this->mysqli->prepare(
            "INSERT INTO messages (thread_id, group_thread_id, user_id, content, attachment_path, reply_to_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiissi", $tidVal, $gtidVal, $this->userId, $con, $att, $replyToId);
        $stmt->execute();
        $msgId = $stmt->insert_id;

        // 最新メッセージIDをキャッシュファイルに記録
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        file_put_contents($cacheDir . '/last_message.id', $msgId);

        // 送信者名・スレッド名を取得
        $uStmt = $this->mysqli->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->bind_param("i", $this->userId);
        $uStmt->execute();
        $senderName = $uStmt->get_result()->fetch_assoc()['username'] ?? 'User';

        $threadName = 'Thread';
        if ($tidVal > 0) {
            $tStmt = $this->mysqli->prepare("SELECT name FROM threads WHERE id = ?");
            $tStmt->bind_param("i", $tidVal);
            $tStmt->execute();
            $threadName = $tStmt->get_result()->fetch_assoc()['name'] ?? 'Thread';
        }

        $messageData = [
            'id'             => $msgId,
            'threadId'       => $tidVal,
            'groupThreadId'  => $gtidVal,
            'userId'         => $this->userId,
            'username'       => $senderName,
            'threadName'     => $threadName,
            'content'        => $con,
            'attachment_path' => $att,
            'reply_to_id'    => $replyToId,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            if ($tidVal > 0) {
                notifyRealtimeServer('new_message', ['threadId' => $tidVal, 'message' => $messageData]);
            } elseif ($gtidVal > 0) {
                notifyRealtimeServer('new_group_message', ['groupThreadId' => $gtidVal, 'message' => $messageData]);
            }
        } catch (\Exception $e) {
            error_log("Realtime notification failed: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'id' => $msgId, 'attachment_path' => $att]);
    }

    public function editMessage(): void
    {
        $this->verifyCsrf();
        $mid  = (int)$this->getParam('message_id', 0);
        $did  = (int)$this->getParam('dm_id', 0);
        $con  = $this->getPost('content', '');

        $stmt = null;
        if ($mid > 0) {
            $stmt = $this->mysqli->prepare("UPDATE messages SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $con, $mid, $this->userId);
        } elseif ($did > 0) {
            $stmt = $this->mysqli->prepare("UPDATE direct_messages SET content = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
            $stmt->bind_param("sii", $con, $did, $this->userId);
        }

        if ($stmt) {
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid message or DM ID']);
        }
    }

    /**
     * [SECURITY FIX] prepare() + バインド化（旧実装は変数直埋め）
     */
    public function deleteMessage(): void
    {
        $this->verifyCsrf();
        $mid  = (int)$this->getParam('message_id', 0);
        $stmt = $this->mysqli->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $mid, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function deleteMessages(): void
    {
        $this->verifyCsrf();
        $idsStr = $this->getParam('message_ids', '');
        if (empty($idsStr)) {
            echo json_encode(['success' => false, 'error' => 'No message IDs provided']);
            return;
        }

        $ids = array_map('intval', explode(',', $idsStr));
        $ids = array_filter($ids, fn($id) => $id > 0);

        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'Invalid message IDs']);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = str_repeat('i', count($ids)) . 'i';

        $stmt   = $this->mysqli->prepare("DELETE FROM messages WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($ids, [$this->userId]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
    }

    public function toggleReaction(): void
    {
        $this->verifyCsrf();
        $mid  = $this->getPost('message_id', 0);
        $emo  = $this->getPost('emoji', '');
        $stmt = $this->mysqli->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
        $stmt->bind_param("iis", $mid, $this->userId, $emo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $favId = $row['id'];
            $stmt  = $this->mysqli->prepare("DELETE FROM message_reactions WHERE id = ?");
            $stmt->bind_param("i", $favId);
            $stmt->execute();
        } else {
            $stmt = $this->mysqli->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $mid, $this->userId, $emo);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
    }

    /**
     * [SECURITY FIX] message_id の thread に自分が参加しているかチェック (IDOR対応)
     */
    public function togglePin(): void
    {
        $this->verifyCsrf();
        $mid = (int)$this->getParam('message_id', 0);

        // メッセージが存在し、かつ自分がアクセス権のあるスレッドか確認
        $chk = $this->mysqli->prepare(
            "SELECT m.id FROM messages m
            JOIN threads t ON m.thread_id = t.id
            WHERE m.id = ?
            LIMIT 1"
        );
        $chk->bind_param("i", $mid);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            return;
        }

        $stmt = $this->mysqli->prepare("UPDATE messages SET is_pinned = NOT is_pinned WHERE id = ?");
        $stmt->bind_param("i", $mid);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    /**
     * [SECURITY FIX] $kw を直接埋め込まず prepared statement でバインド
     */
    public function searchMessages(): void
    {
        $tid  = (int)$this->getGet('thread_id', 0);
        $gtid = (int)$this->getGet('group_thread_id', 0);
        $pid  = (int)$this->getGet('partner_id', 0);
        $kw   = '%' . ($this->getGet('keyword', '')) . '%';

        if ($pid > 0) {
            $stmt = $this->mysqli->prepare(
                "SELECT * FROM direct_messages
                WHERE (sender_id = ? OR receiver_id = ?)
                AND content LIKE ?
                ORDER BY created_at ASC"
            );
            $stmt->bind_param("iis", $this->userId, $this->userId, $kw);
        } elseif ($gtid > 0) {
            $stmt = $this->mysqli->prepare(
                "SELECT * FROM messages WHERE group_thread_id = ? AND content LIKE ? ORDER BY created_at ASC"
            );
            $stmt->bind_param("is", $gtid, $kw);
        } else {
            $stmt = $this->mysqli->prepare(
                "SELECT * FROM messages WHERE thread_id = ? AND content LIKE ? ORDER BY created_at ASC"
            );
            $stmt->bind_param("is", $tid, $kw);
        }

        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getPinnedMessages(): void
    {
        $tid = (int)($this->getGet('thread_id', 0));
        if ($tid <= 0) {
            echo json_encode([]);
            return;
        }
        $stmt = $this->mysqli->prepare(
            "SELECT m.*, u.username, u.avatar_url FROM messages m JOIN users u ON m.user_id = u.id WHERE m.thread_id = ? AND m.is_pinned = 1 ORDER BY m.created_at DESC"
        );
        $stmt->bind_param("i", $tid);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function getAttachments(): void
    {
        $tid = (int)$this->getGet('thread_id', 0);
        $pid = (int)$this->getGet('partner_id', 0);

        if ($tid > 0) {
            $stmt = $this->mysqli->prepare("SELECT attachment_path FROM messages WHERE thread_id = ? AND attachment_path IS NOT NULL");
            $stmt->bind_param("i", $tid);
        } else {
            $stmt = $this->mysqli->prepare("SELECT attachment_path FROM direct_messages WHERE (sender_id = ? OR receiver_id = ?) AND attachment_path IS NOT NULL");
            $stmt->bind_param("ii", $this->userId, $this->userId);
        }

        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function updateTypingStatus(): void
    {
        $this->verifyCsrf();
        $tid      = $this->getParam('thread_id');
        $isTyping = ($this->getParam('is_typing', '0')) === '1';
        $stmt     = $this->mysqli->prepare("UPDATE users SET typing_thread_id = ?, typing_at = ? WHERE id = ?");
        $tval     = $isTyping ? $tid   : null;
        $aval     = $isTyping ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("ssi", $tval, $aval, $this->userId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getTypingUsers(): void
    {
        $tid  = $this->getGet('thread_id', '');
        $stmt = $this->mysqli->prepare("SELECT username FROM users WHERE typing_thread_id = ? AND id != ? AND typing_at > (NOW() - INTERVAL 5 SECOND)");
        $stmt->bind_param("si", $tid, $this->userId);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
