/**
 * PeerConnectionManager
 * Manages RTCPeerConnections using MDN Perfect Negotiation pattern.
 */
class PeerConnectionManager {
    constructor({ userId, iceServers, signalingClient, onRemoteTrack, onStateChange }) {
        this.userId = userId; // Local User ID
        this.iceServers = iceServers || [{ urls: 'stun:stun.l.google.com:19302' }];
        this.signalingClient = signalingClient;
        this.onRemoteTrack = onRemoteTrack;
        this.onStateChange = onStateChange;

        // peerId -> { pc, polite, makingOffer, ignoreOffer, isSettingRemoteAnswerPending, pendingCandidates: [] }
        this.peers = {};
    }

    setIceServers(iceServers) {
        if (iceServers && Array.isArray(iceServers)) {
            this.iceServers = iceServers;
        }
    }

    getOrCreatePeer(peerId, localStream) {
        if (this.peers[peerId]) return this.peers[peerId];

        // Determine who is polite: smaller user ID is polite (or arbitrarily defined unique order)
        const polite = this.userId < peerId;

        const pc = new RTCPeerConnection({ iceServers: this.iceServers });

        const peerObj = {
            pc,
            polite,
            makingOffer: false,
            ignoreOffer: false,
            isSettingRemoteAnswerPending: false,
            pendingCandidates: []
        };

        if (localStream) {
            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
        }

        // Perfect Negotiation: negotiationneeded
        pc.onnegotiationneeded = async () => {
            try {
                peerObj.makingOffer = true;
                await pc.setLocalDescription();
                this.signalingClient.sendOffer(peerId, pc.localDescription);
            } catch (err) {
                console.error(`[PeerConnectionManager] Error during negotiationneeded with ${peerId}:`, err);
            } finally {
                peerObj.makingOffer = false;
            }
        };

        // ICE candidate handler
        pc.onicecandidate = ({ candidate }) => {
            if (candidate) {
                this.signalingClient.sendCandidate(peerId, candidate);
            }
        };

        // Remote Track handler
        pc.ontrack = (event) => {
            const stream = event.streams && event.streams[0] ? event.streams[0] : new MediaStream([event.track]);
            if (typeof this.onRemoteTrack === 'function') {
                this.onRemoteTrack(peerId, stream, event.track);
            }
        };

        // Connection State Change
        pc.onconnectionstatechange = () => {
            if (typeof this.onStateChange === 'function') {
                this.onStateChange(peerId, pc.connectionState);
            }
            if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                console.warn(`[PeerConnectionManager] Peer ${peerId} state changed to ${pc.connectionState}`);
            }
        };

        this.peers[peerId] = peerObj;
        return peerObj;
    }

    async handleOffer(peerId, description, localStream) {
        const peerObj = this.getOrCreatePeer(peerId, localStream);
        const { pc, polite } = peerObj;

        const readyForOffer = !peerObj.makingOffer && (pc.signalingState === 'stable' || peerObj.isSettingRemoteAnswerPending);
        const offerCollision = !readyForOffer;

        peerObj.ignoreOffer = !polite && offerCollision;
        if (peerObj.ignoreOffer) {
            console.warn(`[PeerConnectionManager] Offer collision detected with ${peerId}, ignoring offer because impolite`);
            return;
        }

        peerObj.isSettingRemoteAnswerPending = description.type === 'answer';
        await pc.setRemoteDescription(new RTCSessionDescription(description));
        peerObj.isSettingRemoteAnswerPending = false;

        await this.flushPendingCandidates(peerId);

        if (description.type === 'offer') {
            await pc.setLocalDescription();
            this.signalingClient.sendAnswer(peerId, pc.localDescription);
        }
    }

    async handleAnswer(peerId, description) {
        const peerObj = this.peers[peerId];
        if (!peerObj) return;
        const { pc } = peerObj;

        peerObj.isSettingRemoteAnswerPending = true;
        await pc.setRemoteDescription(new RTCSessionDescription(description));
        peerObj.isSettingRemoteAnswerPending = false;
        await this.flushPendingCandidates(peerId);
    }

    async handleCandidate(peerId, candidate) {
        const peerObj = this.peers[peerId];
        if (!peerObj) {
            return;
        }
        const { pc, ignoreOffer } = peerObj;

        try {
            if (!pc.remoteDescription) {
                peerObj.pendingCandidates.push(candidate);
            } else {
                await pc.addIceCandidate(new RTCIceCandidate(candidate));
            }
        } catch (err) {
            if (!ignoreOffer) {
                console.error(`[PeerConnectionManager] Failed to add ICE candidate from ${peerId}:`, err);
            }
        }
    }

    async flushPendingCandidates(peerId) {
        const peerObj = this.peers[peerId];
        if (!peerObj || !peerObj.pc.remoteDescription) return;

        while (peerObj.pendingCandidates.length > 0) {
            const candidate = peerObj.pendingCandidates.shift();
            try {
                await peerObj.pc.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (err) {
                console.error(`[PeerConnectionManager] Failed to flush candidate for ${peerId}:`, err);
            }
        }
    }

    replaceTrackForAllPeers(oldKind, newTrack) {
        Object.keys(this.peers).forEach(peerId => {
            const { pc } = this.peers[peerId];
            const sender = pc.getSenders().find(s => s.track && s.track.kind === oldKind);
            if (sender) {
                sender.replaceTrack(newTrack);
            }
        });
    }

    removePeer(peerId) {
        if (this.peers[peerId]) {
            try {
                this.peers[peerId].pc.close();
            } catch (e) {}
            delete this.peers[peerId];
        }
    }

    closeAll() {
        Object.keys(this.peers).forEach(peerId => {
            this.removePeer(peerId);
        });
        this.peers = {};
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = PeerConnectionManager;
}
