/**
 * MeetingManager
 * Main orchestrator for SYCS WebRTC Web Meeting System.
 */
class MeetingManager {
    constructor() {
        this.mediaManager = new MediaManager();
        this.signalingClient = new SignalingClient();
        this.peerConnectionManager = null;
        this.ui = new MeetingUI('video-grid');

        this.roomId = null;
        this.roomUuid = null;
        this.userId = (typeof currentUserId !== 'undefined') ? currentUserId : (window.SYCS_CONFIG?.currentUserId || 0);
        this.username = (typeof currentUsername !== 'undefined') ? currentUsername : (window.SYCS_CONFIG?.currentUsername || 'User');
    }

    async start({ threadId = null, groupThreadId = null, dmPartnerId = null, roomId = null }) {
        try {
            // 1. Acquire local media stream
            const localStream = await this.mediaManager.acquireLocalMedia(true);

            // 2. Join meeting via PHP API to validate permissions & get room_id
            let joinRes = null;
            if (roomId) {
                joinRes = { room_id: roomId };
            } else {
                const formData = new FormData();
                const csrfToken = (typeof window.csrfToken !== 'undefined') ? window.csrfToken : '';
                formData.append('csrf_token', csrfToken);
                if (threadId) formData.append('thread_id', threadId);
                if (groupThreadId) formData.append('group_thread_id', groupThreadId);
                if (dmPartnerId) formData.append('dm_partner_id', dmPartnerId);

                const res = await fetch('api.php?action=join_meeting', {
                    method: 'POST',
                    body: formData
                });
                joinRes = await res.json();
                if (!joinRes.success) {
                    alert("会議に参加できませんでした: " + (joinRes.error || "権限がありません"));
                    this.mediaManager.stopAllMedia();
                    return;
                }
            }

            this.roomId = joinRes.room_id;
            this.roomUuid = joinRes.room_uuid || '';

            // 3. Obtain TURN/ICE Server credentials from backend
            let iceServers = [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }];
            try {
                const formData = new FormData();
                const csrfToken = (typeof window.csrfToken !== 'undefined') ? window.csrfToken : '';
                formData.append('csrf_token', csrfToken);
                formData.append('room_id', this.roomId);
                const credRes = await fetch('api.php?action=issue_turn_credentials', {
                    method: 'POST',
                    body: formData
                });
                const credData = await credRes.json();
                if (credData.success && credData.iceServers) {
                    iceServers = credData.iceServers;
                }
            } catch (e) {
                console.warn('[MeetingManager] Failed to fetch TURN credentials, using default STUN:', e);
            }

            // 4. Initialize PeerConnectionManager
            this.peerConnectionManager = new PeerConnectionManager({
                userId: this.userId,
                iceServers,
                signalingClient: this.signalingClient,
                onRemoteTrack: (peerId, stream) => {
                    this.ui.addOrUpdateTile(peerId, stream, false, `ユーザー ${peerId}`);
                },
                onStateChange: (peerId, state) => {
                    this.ui.updatePeerState(peerId, state);
                }
            });

            // 5. Connect to Signaling Server (WebSocket with HTTP Polling fallback)
            this.setupSignalingEvents();
            await this.signalingClient.connect({
                roomId: this.roomId,
                userId: this.userId,
                username: this.username
            });

            // 6. Show UI Modal & render local video
            this.ui.showModal();
            this.ui.addOrUpdateTile(this.userId, localStream, true, '自分');

            // Set screen share callback
            this.mediaManager.onScreenShareEndedCallback = () => {
                const activeStream = this.mediaManager.getActiveVideoStream();
                const cameraTrack = activeStream.getVideoTracks()[0];
                if (cameraTrack && this.peerConnectionManager) {
                    this.peerConnectionManager.replaceTrackForAllPeers('video', cameraTrack);
                }
                this.ui.addOrUpdateTile(this.userId, activeStream, true, '自分');
                this.ui.updateScreenButton(false);
            };

        } catch (err) {
            console.error('[MeetingManager] Start failed:', err);
            alert("メディアデバイスの読み込みに失敗しました。");
            this.leave();
        }
    }

    setupSignalingEvents() {
        this.signalingClient.on('peer_joined', ({ peerId }) => {
            console.log(`[MeetingManager] Peer joined: ${peerId}`);
            if (peerId != this.userId) {
                // Initiate PeerConnection for new peer
                this.peerConnectionManager.getOrCreatePeer(peerId, this.mediaManager.getActiveVideoStream());
            }
        });

        this.signalingClient.on('offer', async ({ peerId, description }) => {
            if (peerId == this.userId) return;
            await this.peerConnectionManager.handleOffer(peerId, description, this.mediaManager.getActiveVideoStream());
        });

        this.signalingClient.on('answer', async ({ peerId, description }) => {
            if (peerId == this.userId) return;
            await this.peerConnectionManager.handleAnswer(peerId, description);
        });

        this.signalingClient.on('ice_candidate', async ({ peerId, candidate }) => {
            if (peerId == this.userId) return;
            await this.peerConnectionManager.handleCandidate(peerId, candidate);
        });

        this.signalingClient.on('peer_left', ({ peerId }) => {
            console.log(`[MeetingManager] Peer left: ${peerId}`);
            if (this.peerConnectionManager) {
                this.peerConnectionManager.removePeer(peerId);
            }
            this.ui.removeTile(peerId);
        });
    }

    toggleMic() {
        const isMuted = this.mediaManager.toggleMic();
        this.ui.updateMicButton(isMuted);
    }

    async toggleCamera() {
        const isVideoOff = await this.mediaManager.toggleCamera();
        this.ui.updateCameraButton(isVideoOff);
        this.ui.setCameraOffOverlay(this.userId, isVideoOff);

        // Update local video tile srcObject if stream gained video track
        const activeStream = this.mediaManager.getActiveVideoStream();
        this.ui.addOrUpdateTile(this.userId, activeStream, true, '自分');

        // Replace track for connected peers if active track changed
        const cameraTrack = activeStream.getVideoTracks()[0];
        if (this.peerConnectionManager && cameraTrack) {
            this.peerConnectionManager.replaceTrackForAllPeers('video', cameraTrack);
        }
    }

    async toggleVideo() {
        return await this.toggleCamera();
    }

    async toggleScreenShare() {
        if (this.mediaManager.isScreenSharing) {
            this.mediaManager.stopScreenShare();
            const activeStream = this.mediaManager.getActiveVideoStream();
            const cameraTrack = activeStream.getVideoTracks()[0];
            if (cameraTrack && this.peerConnectionManager) {
                this.peerConnectionManager.replaceTrackForAllPeers('video', cameraTrack);
            }
            this.ui.addOrUpdateTile(this.userId, activeStream, true, '自分');
            this.ui.updateScreenButton(false);
        } else {
            try {
                const screenStream = await this.mediaManager.startScreenShare();
                const screenTrack = screenStream.getVideoTracks()[0];
                if (screenTrack && this.peerConnectionManager) {
                    this.peerConnectionManager.replaceTrackForAllPeers('video', screenTrack);
                }
                this.ui.addOrUpdateTile(this.userId, screenStream, true, '自分 (画面共有)');
                this.ui.updateScreenButton(true);
            } catch (e) {
                console.warn('[MeetingManager] Screen share failed:', e);
            }
        }
    }

    async leave() {
        if (this.roomId) {
            try {
                const formData = new FormData();
                const csrfToken = (typeof window.csrfToken !== 'undefined') ? window.csrfToken : '';
                formData.append('csrf_token', csrfToken);
                formData.append('room_id', this.roomId);
                await fetch('api.php?action=leave_meeting', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {}
        }

        if (this.peerConnectionManager) {
            this.peerConnectionManager.closeAll();
            this.peerConnectionManager = null;
        }

        this.signalingClient.disconnect();
        this.mediaManager.stopAllMedia();
        this.ui.closeModal();

        this.roomId = null;
        this.roomUuid = null;
    }
}

// Global instance initialization
const meetingManager = new MeetingManager();

function startMeeting(threadId = null, dmPartnerId = null, groupThreadId = null) {
    meetingManager.start({ threadId, dmPartnerId, groupThreadId });
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MeetingManager, meetingManager, startMeeting };
}
