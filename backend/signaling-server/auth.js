/**
 * Authentication module for WebRTC Signaling Server
 */

function verifyJoinToken(data) {
    // Requires valid user_id and room_id
    if (!data || !data.room_id || !data.user_id) {
        return false;
    }
    const userId = parseInt(data.user_id, 10);
    return !isNaN(userId) && userId > 0;
}

module.exports = {
    verifyJoinToken
};
