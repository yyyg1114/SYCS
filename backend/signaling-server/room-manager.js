/**
 * Room Manager for handling rooms and peer connections
 */

class RoomManager {
    constructor() {
        // Map<roomId, Map<userId, WebSocket>>
        this.rooms = new Map();
    }

    joinRoom(roomId, userId, ws) {
        let room = this.rooms.get(roomId);
        if (!room) {
            room = new Map();
            this.rooms.set(roomId, room);
        }
        room.set(userId, ws);
        ws.roomId = roomId;
        ws.userId = userId;
    }

    leaveRoom(ws) {
        const { roomId, userId } = ws;
        if (!roomId || !userId) return null;

        const room = this.rooms.get(roomId);
        if (room) {
            room.delete(userId);
            if (room.size === 0) {
                this.rooms.delete(roomId);
            }
        }
        return { roomId, userId };
    }

    getRoomPeers(roomId) {
        const room = this.rooms.get(roomId);
        if (!room) return [];
        return Array.from(room.keys());
    }

    sendToPeer(roomId, targetUserId, message) {
        const room = this.rooms.get(roomId);
        if (!room) return false;
        const peerWs = room.get(targetUserId);
        if (peerWs && peerWs.readyState === 1) { // 1 = OPEN
            peerWs.send(message);
            return true;
        }
        return false;
    }

    broadcastToRoom(roomId, senderUserId, message) {
        const room = this.rooms.get(roomId);
        if (!room) return;
        for (const [userId, peerWs] of room.entries()) {
            if (userId !== senderUserId && peerWs.readyState === 1) {
                peerWs.send(message);
            }
        }
    }
}

module.exports = new RoomManager();
