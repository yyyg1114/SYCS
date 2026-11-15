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

/** Regular expression for valid base64url string */
const BASE64URL_REGEX = /^[A-Za-z0-9_-]+$/;

/**
 * Validate whether a string is a non-empty valid base64url string
 */
function isValidBase64Url(str) {
    return typeof str === 'string' && str.length > 0 && BASE64URL_REGEX.test(str);
}

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
    if (typeof str !== 'string') return Buffer.alloc(0);
    const padded = str.replace(/-/g, '+').replace(/_/g, '/');
    const pad = (4 - (padded.length % 4)) % 4;
    return Buffer.from(padded + '='.repeat(pad), 'base64');
}

/**
 * Helper to generate a join token (used for testing or backend generation)
 */
function createJoinToken(uid, rid, exp, secret = SIGNALING_SECRET) {
    const payload = JSON.stringify({
        uid: parseInt(uid, 10),
        rid: String(rid),
        exp: exp || (Math.floor(Date.now() / 1000) + TOKEN_TTL)
    });
    const rawPayload = toBase64url(Buffer.from(payload, 'utf8'));
    const sigBuf = crypto.createHmac('sha256', secret).update(rawPayload).digest();
    const rawSig = toBase64url(sigBuf);
    return `${rawPayload}.${rawSig}`;
}

/**
 * Verify a join token received from a WebSocket client.
 *
 * @param {object} data  — raw parsed message object (must have .token, .room_id)
 * @returns {{ userId: number, roomId: string }|null}  — verified identity or null on failure
 */
function verifyJoinToken(data) {
    try {
        if (!SIGNALING_SECRET) {
            console.error('[Auth] SIGNALING_SECRET is not set — refusing all join requests');
            return null;
        }

        if (!data || typeof data !== 'object') {
            console.warn('[Auth] Join rejected: invalid message object');
            return null;
        }

        const token = typeof data.token === 'string' ? data.token : null;
        if (!token) {
            console.warn('[Auth] Join rejected: no token provided');
            return null;
        }

        const parts = token.split('.');
        if (parts.length !== 2) {
            console.warn('[Auth] Join rejected: malformed token (expected 2 parts)');
            return null;
        }

        const [rawPayload, rawSig] = parts;

        // Validate base64url encoding
        if (!isValidBase64Url(rawPayload)) {
            console.warn('[Auth] Join rejected: invalid base64url payload');
            return null;
        }

        if (!isValidBase64Url(rawSig)) {
            console.warn('[Auth] Join rejected: invalid base64url signature');
            return null;
        }

        // Expected signature as raw bytes (Buffer of 32 bytes for SHA256)
        const expectedSigBuf = crypto.createHmac('sha256', SIGNALING_SECRET).update(rawPayload).digest();
        const clientSigBuf = fromBase64url(rawSig);

        // Reject signature-length mismatches BEFORE timingSafeEqual to avoid RangeError DoS
        if (clientSigBuf.length !== expectedSigBuf.length) {
            console.warn(`[Auth] Join rejected: signature length mismatch (${clientSigBuf.length} !== ${expectedSigBuf.length})`);
            return null;
        }

        if (!crypto.timingSafeEqual(clientSigBuf, expectedSigBuf)) {
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

        if (!payload || typeof payload !== 'object') {
            console.warn('[Auth] Join rejected: payload is not an object');
            return null;
        }

        const { uid, rid, exp } = payload;

        // Check expiry
        const nowSec = Math.floor(Date.now() / 1000);
        if (!exp || typeof exp !== 'number' || nowSec > exp) {
            console.warn(`[Auth] Join rejected: token expired (exp=${exp}, now=${nowSec})`);
            return null;
        }

        // Validate uid/rid
        const userId = parseInt(uid, 10);
        if (!userId || userId <= 0) {
            console.warn('[Auth] Join rejected: invalid uid in token');
            return null;
        }

        const roomId = String(rid || '').trim();
        if (!roomId) {
            console.warn('[Auth] Join rejected: invalid rid in token');
            return null;
        }

        // Optionally verify that the room_id in the token matches what the client claims
        const claimedRoomId = String(data.room_id || '').trim();
        if (claimedRoomId && claimedRoomId !== roomId) {
            console.warn(`[Auth] Join rejected: room_id mismatch (token=${roomId}, msg=${claimedRoomId})`);
            return null;
        }

        return { userId, roomId };
    } catch (err) {
        console.warn('[Auth] Join rejected: unexpected token validation error:', err.message);
        return null;
    }
}

module.exports = {
    verifyJoinToken,
    createJoinToken,
    toBase64url,
    fromBase64url,
    isValidBase64Url,
    TOKEN_TTL,
    SIGNALING_SECRET
};

