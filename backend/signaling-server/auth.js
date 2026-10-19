/**
 * Authentication module for WebRTC Signaling Server
 *
 * Join tokens are short-lived HMAC-SHA256 tokens issued by the PHP backend.
 * Format: <base64url(payload)>.<base64url(signature)>
 * Payload JSON: { uid, rid, exp }
 *
 * IMPORTANT: The same secret (SIGNALING_SECRET env var) must be configured
 * in both the PHP backend and this Node.js server.
 */

const crypto = require('crypto');

/** Shared secret — MUST match the PHP backend's SIGNALING_SECRET env var */
const SIGNALING_SECRET = process.env.SIGNALING_SECRET || '';

/** Maximum token age in seconds (matches PHP issuer) */
const TOKEN_TTL = 120;

/**
 * Encode binary to base64url
 */
function toBase64url(buf) {
    return buf.toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/**
 * Decode base64url to Buffer
 */
function fromBase64url(str) {
    const padded = str.replace(/-/g, '+').replace(/_/g, '/');
    const pad = 4 - (padded.length % 4);
    return Buffer.from(pad < 4 ? padded + '='.repeat(pad) : padded, 'base64');
}

/**
 * Verify a join token received from a WebSocket client.
 *
 * @param {object} data  — raw parsed message object (must have .token, .room_id)
 * @returns {{ userId: number, roomId: string }|null}  — verified identity or null on failure
 */
function verifyJoinToken(data) {
    if (!SIGNALING_SECRET) {
        console.error('[Auth] SIGNALING_SECRET is not set — refusing all join requests');
        return null;
    }

    const token = typeof data.token === 'string' ? data.token : null;
    if (!token) {
        console.warn('[Auth] Join rejected: no token provided');
        return null;
    }

    const parts = token.split('.');
    if (parts.length !== 2) {
        console.warn('[Auth] Join rejected: malformed token');
        return null;
    }

    const [rawPayload, rawSig] = parts;

    // Verify HMAC signature
    const expectedSig = toBase64url(
        crypto.createHmac('sha256', SIGNALING_SECRET).update(rawPayload).digest()
    );

    if (!crypto.timingSafeEqual(
        Buffer.from(rawSig),
        Buffer.from(expectedSig)
    )) {
        console.warn('[Auth] Join rejected: invalid signature');
        return null;
    }

    // Decode and parse payload
    let payload;
    try {
        payload = JSON.parse(fromBase64url(rawPayload).toString('utf8'));
    } catch (e) {
        console.warn('[Auth] Join rejected: payload parse error');
        return null;
    }

    const { uid, rid, exp } = payload;

    // Check expiry
    const nowSec = Math.floor(Date.now() / 1000);
    if (!exp || nowSec > exp) {
        console.warn(`[Auth] Join rejected: token expired (exp=${exp}, now=${nowSec})`);
        return null;
    }

    // Validate uid/rid
    const userId = parseInt(uid, 10);
    if (!userId || userId <= 0) {
        console.warn('[Auth] Join rejected: invalid uid in token');
        return null;
    }

    const roomId = String(rid);
    if (!roomId) {
        console.warn('[Auth] Join rejected: invalid rid in token');
        return null;
    }

    // Optionally verify that the room_id in the token matches what the client claims
    const claimedRoomId = String(data.room_id);
    if (claimedRoomId && claimedRoomId !== roomId) {
        console.warn(`[Auth] Join rejected: room_id mismatch (token=${roomId}, msg=${claimedRoomId})`);
        return null;
    }

    return { userId, roomId };
}

module.exports = {
    verifyJoinToken,
    TOKEN_TTL,
    SIGNALING_SECRET
};
