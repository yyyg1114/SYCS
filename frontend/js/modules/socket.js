/**
 * SYCS Realtime Module (SSE-based)
 *
 * Socket.io / realtime-server (Node.js) を廃止し、
 * ブラウザ標準の EventSource API (Server-Sent Events) に移行。
 * コールバックシグネチャは旧 initSocket() と完全互換。
 */

/** 現在アクティブな EventSource インスタンス */
export let socket = null;

/** 現在の lastMsgId / lastDmId（再接続時のカーソル送信に使用） */
let _lastMsgId = 0;
let _lastDmId  = 0;

/**
 * SSE ストリームを開始する。
 * @param {number|string} userId     - 現在ログイン中のユーザーID（将来の拡張用）
 * @param {object}        callbacks  - イベントハンドラのコールバック群
 *   - onConnect(void)
 *   - onConnectError(error)
 *   - onNewMessage(data)
 *   - onNewGroupMessage(data)
 *   - onNewDm(data)
 *   - onStatusChange(data)   ※ 将来の status SSE イベント用
 *   - onTyping(data)         ※ 将来の typing SSE イベント用
 */
export function initSocket(userId, callbacks) {
    // すでに接続中なら閉じてから再接続
    if (socket && socket.readyState !== EventSource.CLOSED) {
        socket.close();
    }

    const url = `index.php?api=sse&last_msg_id=${_lastMsgId}&last_dm_id=${_lastDmId}`;
    const es   = new EventSource(url);
    socket = es;

    // --- 接続確立 ---
    es.addEventListener('connected', () => {
        console.log('[SSE] Realtime connection established.');
        if (callbacks.onConnect) callbacks.onConnect();
    });

    // --- 新着スレッドメッセージ ---
    es.addEventListener('new_message', (event) => {
        try {
            const data = JSON.parse(event.data);
            _lastMsgId = Math.max(_lastMsgId, data.id || 0);
            if (callbacks.onNewMessage) callbacks.onNewMessage(data);
        } catch (e) {
            console.warn('[SSE] Failed to parse new_message:', e);
        }
    });

    // --- 新着グループメッセージ ---
    es.addEventListener('new_group_message', (event) => {
        try {
            const data = JSON.parse(event.data);
            _lastMsgId = Math.max(_lastMsgId, data.id || 0);
            if (callbacks.onNewGroupMessage) callbacks.onNewGroupMessage(data);
        } catch (e) {
            console.warn('[SSE] Failed to parse new_group_message:', e);
        }
    });

    // --- 新着ダイレクトメッセージ ---
    es.addEventListener('new_dm', (event) => {
        try {
            const data = JSON.parse(event.data);
            _lastDmId = Math.max(_lastDmId, data.id || 0);
            if (callbacks.onNewDm) callbacks.onNewDm(data);
        } catch (e) {
            console.warn('[SSE] Failed to parse new_dm:', e);
        }
    });

    // --- ハートビート（カーソル同期） ---
    es.addEventListener('heartbeat', (event) => {
        try {
            const data = JSON.parse(event.data);
            if (data.last_msg_id) _lastMsgId = data.last_msg_id;
            if (data.last_dm_id)  _lastDmId  = data.last_dm_id;
        } catch (_) { /* ignore */ }
    });

    // --- エラー / 切断（ブラウザが自動再接続するため警告のみ） ---
    es.onerror = (error) => {
        console.warn('[SSE] Connection lost. Browser will auto-reconnect...', error);
        if (callbacks.onConnectError) callbacks.onConnectError(error);
    };

    return es;
}
