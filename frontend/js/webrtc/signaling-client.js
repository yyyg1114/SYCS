/**
 * SignalingClient
 * Handles WebSocket signaling communication with fallback to HTTP polling.
 */
class SignalingClient {
constructor() {
this.ws = null;
this.roomId = null;
this.userId = null;
this.username = null;
this.listeners = {};
this.isPolling = false;
this.pollingInterval = null;
this.lastSignalingId = 0;
this.serverUrl = null;
}

on(event, handler) {
if (!this.listeners[event]) this.listeners[event] = [];
this.listeners[event].push(handler);
}

emitEvent(event, data) {
const handlers = this.listeners[event] || [];
handlers.forEach(h => h(data));
}

async connect({ roomId, userId, username, signalingUrl }) {
this.roomId = roomId;
this.userId = userId;
this.username = username;
this.serverUrl = signalingUrl || this.getWebsocketUrl();

return new Promise((resolve) => {
    try {
        this.ws = new WebSocket(this.serverUrl);

        this.ws.onopen = () => {
            console.log('[SignalingClient] Connected to WebSocket server');
            this.send({
                type: 'join',
                room_id: this.roomId,
                user_id: this.userId,
                username: this.username
            });
            resolve(true);
        };

        this.ws.onmessage = (event) => {
            this.handleIncomingMessage(event.data);
        };

        this.ws.onerror = (err) => {
            console.warn('[SignalingClient] WebSocket error, starting polling fallback:', err);
            this.startPollingFallback();
            resolve(false);
        };

        this.ws.onclose = () => {
            console.warn('[SignalingClient] WebSocket connection closed');
            if (!this.isPolling) {
                this.startPollingFallback();
            }
        };
    } catch (e) {
        console.warn('[SignalingClient] WebSocket connection failed:', e);
        this.startPollingFallback();
        resolve(false);
    }
});
}

getWebsocketUrl() {
const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
const host = location.hostname || 'localhost';
return `${protocol}//${host}:8080`;
}

send(messageObj) {
if (this.ws && this.ws.readyState === WebSocket.OPEN) {
    this.ws.send(JSON.stringify(messageObj));
} else if (this.isPolling) {
    this.sendViaHttp(messageObj);
}
}

sendOffer(peerId, description) {
this.send({
    type: 'offer',
    peer_id: peerId,
    description
});
}

sendAnswer(peerId, description) {
this.send({
    type: 'answer',
    peer_id: peerId,
    description
});
}

sendCandidate(peerId, candidate) {
this.send({
    type: 'candidate',
    peer_id: peerId,
    candidate
});
}

handleIncomingMessage(rawData) {
try {
    const data = JSON.parse(rawData);
    if (!data || !data.type) return;

    switch (data.type) {
        case 'peer_joined':
            this.emitEvent('peer_joined', { peerId: data.peer_id, userId: data.user_id, username: data.username });
            break;
        case 'offer':
            this.emitEvent('offer', { peerId: data.peer_id, description: data.description });
            break;
        case 'answer':
            this.emitEvent('answer', { peerId: data.peer_id, description: data.description });
            break;
        case 'ice_candidate':
            this.emitEvent('ice_candidate', { peerId: data.peer_id, candidate: data.candidate });
            break;
        case 'peer_left':
            this.emitEvent('peer_left', { peerId: data.peer_id });
            break;
        default:
            break;
    }
} catch (e) {
    console.error('[SignalingClient] Message parse error:', e);
}
}

async sendViaHttp(messageObj) {
try {
const csrfToken = window.SYCS_CONFIG?.csrfToken || '';

const formData = new FormData();
formData.append('csrf_token', csrfToken);
formData.append('room_id', String(this.roomId));
formData.append('receiver_id', String(messageObj.peer_id || 0));
formData.append('type', messageObj.type);
formData.append(
    'content',
    JSON.stringify(
        messageObj.description ||
        messageObj.candidate ||
        {}
    )
);

const res = await fetch('index.php?api=send_signaling', {
    method: 'POST',
    body: formData
});

if (!res.ok) {
    console.error(
        '[SignalingClient] HTTP send signaling failed:',
        res.status,
        await res.text()
    );
}
} catch (e) {
console.error(
    '[SignalingClient] HTTP send signaling failed:',
    e
);
}
}

async pollParticipants() {
const params = new URLSearchParams({
    api: 'get_meeting_participants',
    room_id: String(this.roomId)
});

    const res = await fetch(`index.php?${params.toString()}`);

    if (!res.ok) {
        console.error(
            '[SignalingClient] Participant polling error:',
            res.status
        );
        return;
    }

    const data = await res.json();

    if (!data.success || !Array.isArray(data.participants)) {
        return;
    }

    for (const participant of data.participants) {
        const peerId = Number(participant.user_id);

        if (!peerId || peerId === Number(this.userId)) {
            continue;
        }

        this.emitEvent('peer_joined', {
            peerId,
            userId: peerId,
            username: participant.username
        });
    }
await this.pollParticipants();
}




startPollingFallback() {

if (this.isPolling) return;

this.isPolling = true;
console.log('[SignalingClient] HTTP polling fallback activated');

if (this.pollingInterval) {
clearInterval(this.pollingInterval);
}

this.pollingInterval = setInterval(async () => {
if (!this.roomId) return;

try {
    const params = new URLSearchParams({
        api: 'get_signaling',
        room_id: String(this.roomId),
        last_id: String(this.lastSignalingId)
    });

    const res = await fetch(`index.php?${params.toString()}`);

    if (!res.ok) {
        console.error(
            '[SignalingClient] Polling HTTP error:',
            res.status,
            await res.text()
        );
        return;
    }

    const msgs = await res.json();

    if (!Array.isArray(msgs)) return;

    for (const msg of msgs) {
        this.lastSignalingId = Math.max(
            this.lastSignalingId,
            Number(msg.id) || 0
        );

        const content =
            typeof msg.content === 'string'
                ? JSON.parse(msg.content)
                : msg.content;

        if (msg.type === 'offer') {
            this.emitEvent('offer', {
                peerId: msg.sender_id,
                description: content
            });
        } else if (msg.type === 'answer') {
            this.emitEvent('answer', {
                peerId: msg.sender_id,
                description: content
            });
        } else if (
            msg.type === 'candidate' ||
            msg.type === 'ice_candidate'
        ) {
            this.emitEvent('ice_candidate', {
                peerId: msg.sender_id,
                candidate: content
            });
        }
    }
} catch (e) {
    console.error(
        '[SignalingClient] Polling error:',
        e
    );
}
}, 2000);
}

disconnect() {
if (this.pollingInterval) {
    clearInterval(this.pollingInterval);
    this.pollingInterval = null;
}
this.isPolling = false;
if (this.ws) {
    this.ws.close();
    this.ws = null;
}
this.listeners = {};
}
}

if (typeof module !== 'undefined' && module.exports) {
module.exports = SignalingClient;
}
