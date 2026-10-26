/**
 * Regression tests for Issue #15:
 *  - DoS prevention on malformed signature / length mismatches
 *  - Exact origin matching
 */

const assert = require('assert');
const crypto = require('crypto');

// Set test secret
process.env.SIGNALING_SECRET = 'test-secret-for-regression-tests-123456';
const { verifyJoinToken, createJoinToken, toBase64url, fromBase64url } = require('./auth');
const { normalizeOrigin } = require('./server');

console.log('--- Running WebRTC Signaling Auth & DoS Regression Tests ---');

let passed = 0;
let failed = 0;

function it(description, fn) {
    try {
        fn();
        console.log(`  [PASS] ${description}`);
        passed++;
    } catch (err) {
        console.error(`  [FAIL] ${description}`);
        console.error(err);
        failed++;
    }
}

// -----------------------------------------------------------------
// 1. Signature Length & Malformed Token Tests (Issue #15 P0 DoS Fix)
// -----------------------------------------------------------------
const secret = process.env.SIGNALING_SECRET;
const validPayloadObj = { uid: 1, rid: 'room_100', exp: Math.floor(Date.now() / 1000) + 120 };
const validPayloadJson = JSON.stringify(validPayloadObj);
const validRawPayload = toBase64url(Buffer.from(validPayloadJson, 'utf8'));

it('1. Valid token succeeds', () => {
    const validToken = createJoinToken(1, 'room_100', validPayloadObj.exp, secret);
    const result = verifyJoinToken({ token: validToken, room_id: 'room_100' });
    assert.notStrictEqual(result, null, 'Valid token should be accepted');
    assert.strictEqual(result.userId, 1);
    assert.strictEqual(result.roomId, 'room_100');
});

it('2. Empty signature does not throw and is rejected', () => {
    const token = `${validRawPayload}.`;
    let result;
    assert.doesNotThrow(() => {
        result = verifyJoinToken({ token, room_id: 'room_100' });
    }, 'Must not throw RangeError on empty signature');
    assert.strictEqual(result, null, 'Empty signature must be rejected');
});

it('3. Short signature (e.g. 5 bytes) does not throw RangeError and is rejected', () => {
    const shortSig = toBase64url(Buffer.from('short'));
    const token = `${validRawPayload}.${shortSig}`;
    let result;
    assert.doesNotThrow(() => {
        result = verifyJoinToken({ token, room_id: 'room_100' });
    }, 'Must not throw RangeError on short signature');
    assert.strictEqual(result, null, 'Short signature must be rejected');
});

it('4. Long signature (e.g. 64 bytes) does not throw RangeError and is rejected', () => {
    const longSig = toBase64url(crypto.randomBytes(64));
    const token = `${validRawPayload}.${longSig}`;
    let result;
    assert.doesNotThrow(() => {
        result = verifyJoinToken({ token, room_id: 'room_100' });
    }, 'Must not throw RangeError on long signature');
    assert.strictEqual(result, null, 'Long signature must be rejected');
});

it('5. Invalid base64url characters in signature are rejected without throwing', () => {
    const invalidSig = 'invalid!!characters@@##$$%%';
    const token = `${validRawPayload}.${invalidSig}`;
    let result;
    assert.doesNotThrow(() => {
        result = verifyJoinToken({ token, room_id: 'room_100' });
    }, 'Must not throw on invalid base64url characters');
    assert.strictEqual(result, null, 'Invalid base64url signature must be rejected');
});

it('6. Valid-length (32 bytes) but invalid signature is rejected normally', () => {
    const wrongSig = toBase64url(crypto.randomBytes(32));
    const token = `${validRawPayload}.${wrongSig}`;
    let result;
    assert.doesNotThrow(() => {
        result = verifyJoinToken({ token, room_id: 'room_100' });
    });
    assert.strictEqual(result, null, 'Invalid signature with correct length must be rejected');
});

it('7. Malformed token formats (no dots, multiple dots) are rejected', () => {
    assert.strictEqual(verifyJoinToken({ token: 'nodots', room_id: 'room_100' }), null);
    assert.strictEqual(verifyJoinToken({ token: 'a.b.c', room_id: 'room_100' }), null);
    assert.strictEqual(verifyJoinToken({ token: null, room_id: 'room_100' }), null);
    assert.strictEqual(verifyJoinToken({}), null);
});

it('8. Expired token is rejected', () => {
    const expiredExp = Math.floor(Date.now() / 1000) - 10;
    const expiredToken = createJoinToken(1, 'room_100', expiredExp, secret);
    const result = verifyJoinToken({ token: expiredToken, room_id: 'room_100' });
    assert.strictEqual(result, null, 'Expired token must be rejected');
});

it('9. Room ID mismatch between claimed room and token room is rejected', () => {
    const validToken = createJoinToken(1, 'room_100', validPayloadObj.exp, secret);
    const result = verifyJoinToken({ token: validToken, room_id: 'different_room' });
    assert.strictEqual(result, null, 'Mismatched room_id must be rejected');
});

// -----------------------------------------------------------------
// 2. Exact Origin Matching Tests (Issue #15 P1 Origin Fix)
// -----------------------------------------------------------------
console.log('\n--- Running Exact Origin Matching Tests ---');

it('10. Exact origin matches allowed origin', () => {
    const allowed = 'https://app.example.com';
    const allowedSet = new Set([normalizeOrigin(allowed)]);
    const testOrigin = 'https://app.example.com';
    assert.strictEqual(allowedSet.has(normalizeOrigin(testOrigin)), true);
});

it('11. Prefix-matching bypass (app.example.com.attacker.invalid) is REJECTED', () => {
    const allowed = 'https://app.example.com';
    const allowedSet = new Set([normalizeOrigin(allowed)]);
    const attackerOrigin = 'https://app.example.com.attacker.invalid';
    assert.strictEqual(allowedSet.has(normalizeOrigin(attackerOrigin)), false, 'Attacker sub/super-domain must be rejected');
});

it('12. Scheme mismatch (http vs https) is REJECTED', () => {
    const allowed = 'https://app.example.com';
    const allowedSet = new Set([normalizeOrigin(allowed)]);
    const httpOrigin = 'http://app.example.com';
    assert.strictEqual(allowedSet.has(normalizeOrigin(httpOrigin)), false, 'Scheme mismatch must be rejected');
});

it('13. Port mismatch is REJECTED', () => {
    const allowed = 'https://app.example.com:8443';
    const allowedSet = new Set([normalizeOrigin(allowed)]);
    const otherPortOrigin = 'https://app.example.com:9443';
    assert.strictEqual(allowedSet.has(normalizeOrigin(otherPortOrigin)), false, 'Port mismatch must be rejected');
});

it('14. Malformed origin strings are normalized safely without throwing', () => {
    assert.strictEqual(normalizeOrigin('not a url'), '');
    assert.strictEqual(normalizeOrigin(''), '');
    assert.strictEqual(normalizeOrigin(null), '');
});

console.log(`\nTests finished: ${passed} passed, ${failed} failed.`);
if (failed > 0) {
    process.exit(1);
}
process.exit(0);
