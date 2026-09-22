/**
 * Protocol handling for SYCS WebRTC Signaling Server
 * Standardized message types:
 * - join: { type: "join", room_id, user_id, token }
 * - peer_joined: { type: "peer_joined", peer_id, user_id, username }
 * - offer: { type: "offer", peer_id, description }
 * - answer: { type: "answer", peer_id, description }
 * - ice_candidate: { type: "ice_candidate", peer_id, candidate }
 * - peer_left: { type: "peer_left", peer_id }
 * - error: { type: "error", code, message }
 */

const MESSAGE_TYPES = {
    JOIN: 'join',
    PEER_JOINED: 'peer_joined',
    OFFER: 'offer',
    ANSWER: 'answer',
    ICE_CANDIDATE: 'ice_candidate',
    PEER_LEFT: 'peer_left',
    ERROR: 'error'
};

function parseMessage(rawMessage) {
    try {
        return JSON.parse(rawMessage);
    } catch (e) {
        return null;
    }
}

function formatMessage(type, payload = {}) {
    return JSON.stringify({ type, ...payload });
}

module.exports = {
    MESSAGE_TYPES,
    parseMessage,
    formatMessage
};
