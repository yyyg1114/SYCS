/**
 * SYCS WebRTC Signaling Server — v2.2.41
 *
 * Security enhancements:
 *   - All join requests must present a valid HMAC-SHA256 token (issued by PHP)
 *   - Authenticated user_id is bound to the WS connection (client-supplied user_id ignored)
 *   - Origin validation (ALLOWED_ORIGINS env var, comma-separated)
 *   - Maximum message payload size (MAX_MSG_BYTES env var, default 65536)
 *   - Per-connection rate limiting (MAX_MSGS_PER_SEC env var, default 20)
 *   - Maximum concurrent connections (MAX_CONNECTIONS env var, default 500)
 *   - Room size limit (MAX_ROOM_SIZE env var, default 50)
 *   - Heartbeat / idle timeout (PING_INTERVAL_MS, IDLE_TIMEOUT_MS)
 */

const { WebSocketServer } = require('ws');
const http = require('http');
const roomManager = require('./room-manager');
const { verifyJoinToken, SIGNALING_SECRET } = require('./auth');
const { MESSAGE_TYPES, parseMessage, formatMessage } = require('./protocol');

// ----------------------------------------------------------------
// Configuration
// ----------------------------------------------------------------
const PORT            = parseInt(process.env.SIGNALING_PORT  || '8080', 10);
const MAX_MSG_BYTES   = parseInt(process.env.MAX_MSG_BYTES   || '65536', 10);
const MAX_MSGS_PER_SEC= parseInt(process.env.MAX_MSGS_PER_SEC|| '20', 10);
const MAX_CONNECTIONS = parseInt(process.env.MAX_CONNECTIONS || '500', 10);
const MAX_ROOM_SIZE   = parseInt(process.env.MAX_ROOM_SIZE   || '50', 10);
const PING_INTERVAL_MS= parseInt(process.env.PING_INTERVAL_MS|| '30000', 10);
const IDLE_TIMEOUT_MS = parseInt(process.env.IDLE_TIMEOUT_MS || '60000', 10);

// Allowed origin(s) — empty means no Origin check (useful in dev/proxy setups)
const ALLOWED_ORIGINS = process.env.ALLOWED_ORIGINS
    ? process.env.ALLOWED_ORIGINS.split(',').map(o => o.trim()).filter(Boolean)
    : [];

// Fail-fast if secret is not configured
if (!SIGNALING_SECRET) {
    console.error('[Signaling] FATAL: SIGNALING_SECRET env var is not set. Exiting.');
    process.exit(1);
}

// ----------------------------------------------------------------
// HTTP server (health check)
// ----------------------------------------------------------------
const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', connections: wss.clients.size }));
    } else {
        res.writeHead(404);
        res.end();
    }
});

// ----------------------------------------------------------------
// WebSocket server
// ----------------------------------------------------------------
const wss = new WebSocketServer({
    server,
    maxPayload: MAX_MSG_BYTES,
    // Origin verification callback
    verifyClient: ({ req }, cb) => {
        if (ALLOWED_ORIGINS.length === 0) {
            return cb(true);
        }
        const origin = req.headers['origin'] || '';
        if (ALLOWED_ORIGINS.some(o => origin === o || origin.startsWith(o))) {
            return cb(true);
        }
        console.warn(`[Signaling] Rejected connection from disallowed origin: ${origin}`);
        cb(false, 403, 'Forbidden');
    }
});

// ----------------------------------------------------------------
// Heartbeat — detect stale connections
// ----------------------------------------------------------------
const pingInterval = setInterval(() => {
    const now = Date.now();
    wss.clients.forEach(ws => {
        if (ws.readyState !== ws.OPEN) return;

        // Check idle timeout
        if (ws._lastActivity && (now - ws._lastActivity) > IDLE_TIMEOUT_MS) {
            console.warn(`[Signaling] Idle timeout for user ${ws.userId}, closing`);
            ws.terminate();
            return;
        }

        // Standard ping/pong liveness check
        if (ws._isAlive === false) {
            ws.terminate();
            return;
        }
        ws._isAlive = false;
        ws.ping();
    });
}, PING_INTERVAL_MS);

wss.on('close', () => clearInterval(pingInterval));

// ----------------------------------------------------------------
// Connection handler
// ----------------------------------------------------------------
wss.on('connection', (ws, req) => {
    // Enforce global connection limit
    if (wss.clients.size > MAX_CONNECTIONS) {
        console.warn('[Signaling] Connection limit reached, rejecting new connection');
        ws.close(1013, 'Server capacity exceeded');
        return;
    }

    // Per-connection state
    ws._isAlive     = true;
    ws._lastActivity= Date.now();
    ws._msgCount    = 0;        // messages in current second window
    ws._windowStart = Date.now();
    ws.userId       = null;     // set after successful JOIN auth
    ws.roomId       = null;

    ws.on('pong', () => {
        ws._isAlive = true;
        ws._lastActivity = Date.now();
    });

    ws.on('message', (rawMessage, isBinary) => {
        // Reject binary frames
        if (isBinary) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Binary messages not supported' }));
            return;
        }

        // Payload size guard (ws maxPayload handles this too, but be explicit)
        if (rawMessage.length > MAX_MSG_BYTES) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Message too large' }));
            return;
        }

        // Rate limiting per connection
        const now = Date.now();
        if (now - ws._windowStart > 1000) {
            ws._msgCount    = 0;
            ws._windowStart = now;
        }
        ws._msgCount++;
        if (ws._msgCount > MAX_MSGS_PER_SEC) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Rate limit exceeded' }));
            return;
        }

        ws._lastActivity = now;

        const msg = parseMessage(rawMessage);
        if (!msg || !msg.type) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Invalid message format' }));
            return;
        }

        // ── JOIN ──────────────────────────────────────────────────
        if (msg.type === MESSAGE_TYPES.JOIN) {
            // Only allow one JOIN per connection
            if (ws.userId !== null) {
                ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Already joined' }));
                return;
            }

            // Verify token — this is the only place we trust client-supplied identity
            const identity = verifyJoinToken(msg);
            if (!identity) {
                ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Unauthorized' }));
                ws.close(4001, 'Unauthorized');
                return;
            }

            const { userId, roomId } = identity;

            // Room size limit
            const currentPeers = roomManager.getRoomPeers(roomId);
            if (currentPeers.length >= MAX_ROOM_SIZE) {
                ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Room is full' }));
                ws.close(4003, 'Room full');
                return;
            }

            // Use username from token payload if available (ignore client msg.username)
            const username = identity.username || `User_${userId}`;

            // Register authenticated identity on socket
            roomManager.joinRoom(roomId, userId, ws);
            console.log(`[Signaling] User ${userId} (${username}) joined room ${roomId}`);

            // Notify joining user of existing peers
            const existingPeers = roomManager.getRoomPeers(roomId).filter(id => id !== userId);
            existingPeers.forEach(peerId => {
                ws.send(formatMessage(MESSAGE_TYPES.PEER_JOINED, {
                    peer_id: peerId,
                    user_id: peerId
                }));
            });

            // Notify others that this user joined
            roomManager.broadcastToRoom(roomId, userId, formatMessage(MESSAGE_TYPES.PEER_JOINED, {
                peer_id: userId,
                user_id: userId,
                username
            }));
            return;
        }

        // ── All other message types require an authenticated JOIN first ──
        if (ws.userId === null) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Unauthorized: join first' }));
            return;
        }

        switch (msg.type) {
            case MESSAGE_TYPES.OFFER:
            case MESSAGE_TYPES.ANSWER: {
                const targetPeerId = parseInt(msg.peer_id, 10);
                if (ws.roomId && targetPeerId && targetPeerId !== ws.userId) {
                    roomManager.sendToPeer(ws.roomId, targetPeerId, formatMessage(msg.type, {
                        peer_id: ws.userId,   // server-authoritative sender id
                        description: msg.description
                    }));
                }
                break;
            }

            case MESSAGE_TYPES.ICE_CANDIDATE: {
                const targetPeerId = parseInt(msg.peer_id, 10);
                if (ws.roomId && targetPeerId && targetPeerId !== ws.userId) {
                    roomManager.sendToPeer(ws.roomId, targetPeerId, formatMessage(MESSAGE_TYPES.ICE_CANDIDATE, {
                        peer_id: ws.userId,   // server-authoritative sender id
                        candidate: msg.candidate
                    }));
                }
                break;
            }

            case MESSAGE_TYPES.PEER_LEFT: {
                handleDisconnect(ws);
                break;
            }

            default:
                ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: `Unknown message type: ${msg.type}` }));
        }
    });

    ws.on('close', () => handleDisconnect(ws));
    ws.on('error', (err) => {
        console.error('[Signaling] WebSocket error:', err.message);
        handleDisconnect(ws);
    });
});

// ----------------------------------------------------------------
// Disconnect handler
// ----------------------------------------------------------------
function handleDisconnect(ws) {
    const leftInfo = roomManager.leaveRoom(ws);
    if (leftInfo) {
        console.log(`[Signaling] User ${leftInfo.userId} left room ${leftInfo.roomId}`);
        roomManager.broadcastToRoom(leftInfo.roomId, leftInfo.userId, formatMessage(MESSAGE_TYPES.PEER_LEFT, {
            peer_id: leftInfo.userId
        }));
    }
}

// ----------------------------------------------------------------
// Start
// ----------------------------------------------------------------
server.listen(PORT, () => {
    console.log(`[Signaling] Server running on port ${PORT}`);
    console.log(`[Signaling] Max connections: ${MAX_CONNECTIONS}, Max room size: ${MAX_ROOM_SIZE}`);
    console.log(`[Signaling] Allowed origins: ${ALLOWED_ORIGINS.length ? ALLOWED_ORIGINS.join(', ') : '(any — set ALLOWED_ORIGINS to restrict)'}`);
});
