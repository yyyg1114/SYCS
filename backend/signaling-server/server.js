/**
 * SYCS WebRTC Signaling Server
 */

const { WebSocketServer } = require('ws');
const http = require('http');
const roomManager = require('./room-manager');
const { verifyJoinToken } = require('./auth');
const { MESSAGE_TYPES, parseMessage, formatMessage } = require('./protocol');

const PORT = process.env.SIGNALING_PORT || 8080;

const server = http.createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('SYCS Signaling Server Running\n');
});

const wss = new WebSocketServer({ server });

wss.on('connection', (ws, req) => {
    console.log('[Signaling] New WebSocket connection from', req.socket.remoteAddress);

    ws.on('message', (rawMessage) => {
        const msg = parseMessage(rawMessage);
        if (!msg || !msg.type) {
            ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Invalid message format' }));
            return;
        }

        switch (msg.type) {
            case MESSAGE_TYPES.JOIN: {
                if (!verifyJoinToken(msg)) {
                    ws.send(formatMessage(MESSAGE_TYPES.ERROR, { message: 'Unauthorized join request' }));
                    return;
                }

                const roomId = String(msg.room_id);
                const userId = parseInt(msg.user_id, 10);
                const username = msg.username || `User_${userId}`;

                // Register in room
                roomManager.joinRoom(roomId, userId, ws);
                console.log(`[Signaling] User ${userId} (${username}) joined room ${roomId}`);

                // Notify existing members in room
                const existingPeers = roomManager.getRoomPeers(roomId).filter(id => id !== userId);
                
                // Notify user of existing peers
                existingPeers.forEach(peerId => {
                    ws.send(formatMessage(MESSAGE_TYPES.PEER_JOINED, {
                        peer_id: peerId,
                        user_id: peerId
                    }));
                });

                // Notify others that a new peer joined
                roomManager.broadcastToRoom(roomId, userId, formatMessage(MESSAGE_TYPES.PEER_JOINED, {
                    peer_id: userId,
                    user_id: userId,
                    username
                }));
                break;
            }

            case MESSAGE_TYPES.OFFER: {
                const targetPeerId = parseInt(msg.peer_id, 10);
                if (ws.roomId && targetPeerId) {
                    roomManager.sendToPeer(ws.roomId, targetPeerId, formatMessage(MESSAGE_TYPES.OFFER, {
                        peer_id: ws.userId,
                        description: msg.description
                    }));
                }
                break;
            }

            case MESSAGE_TYPES.ANSWER: {
                const targetPeerId = parseInt(msg.peer_id, 10);
                if (ws.roomId && targetPeerId) {
                    roomManager.sendToPeer(ws.roomId, targetPeerId, formatMessage(MESSAGE_TYPES.ANSWER, {
                        peer_id: ws.userId,
                        description: msg.description
                    }));
                }
                break;
            }

            case MESSAGE_TYPES.ICE_CANDIDATE: {
                const targetPeerId = parseInt(msg.peer_id, 10);
                if (ws.roomId && targetPeerId) {
                    roomManager.sendToPeer(ws.roomId, targetPeerId, formatMessage(MESSAGE_TYPES.ICE_CANDIDATE, {
                        peer_id: ws.userId,
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

    ws.on('close', () => {
        handleDisconnect(ws);
    });

    ws.on('error', (err) => {
        console.error('[Signaling] WebSocket error:', err.message);
        handleDisconnect(ws);
    });
});

function handleDisconnect(ws) {
    const leftInfo = roomManager.leaveRoom(ws);
    if (leftInfo) {
        console.log(`[Signaling] User ${leftInfo.userId} left room ${leftInfo.roomId}`);
        roomManager.broadcastToRoom(leftInfo.roomId, leftInfo.userId, formatMessage(MESSAGE_TYPES.PEER_LEFT, {
            peer_id: leftInfo.userId
        }));
    }
}

server.listen(PORT, () => {
    console.log(`[Signaling] Server is running on port ${PORT}`);
});
