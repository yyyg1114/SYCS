/**
 * WebRTC Meeting Manager
 * Handles Mesh topology P2P communication for SYCS
 */

class MeetingManager {
    constructor() {
        this.localStream = null;
        this.peers = {}; // peerUserId -> { connection, stream }
        this.roomId = null;
        this.lastSignalingId = 0;
        this.isMuted = false;
        this.isVideoOff = false;
        this.pollingInterval = null;
        this.iceServers = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };
    }

    async init() {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            this.addVideoTrack(currentUserId, this.localStream, true);
        } catch (err) {
            console.error('Error accessing media devices:', err);
            alert('カメラまたはマイクにアクセスできませんでした。');
            throw err;
        }
    }

    async start(threadId, dmPartnerId) {
        await this.init();
        
        const body = new FormData();
        if (threadId) body.append('thread_id', threadId);
        if (dmPartnerId) body.append('dm_partner_id', dmPartnerId);
        
        const res = await api('join_meeting', 'POST', body);
        if (res.error) {
            alert(res.error);
            return;
        }

        this.roomId = res.room_id;
        document.getElementById('meeting-modal').showModal();

        // Start polling for signaling
        this.startPolling();

        // If it's a DM, we can automatically "knock" the partner
        // In a thread, we'll just wait for others to join and send offers
        if (dmPartnerId) {
            this.initiateCall(dmPartnerId);
        }
    }

    startPolling() {
        this.pollingInterval = setInterval(async () => {
            if (!this.roomId) return;
            const res = await api(`get_signaling&room_id=${this.roomId}&last_id=${this.lastSignalingId}`);
            if (res && res.length > 0) {
                for (const msg of res) {
                    this.lastSignalingId = Math.max(this.lastSignalingId, msg.id);
                    await this.handleSignaling(msg);
                }
            }
        }, 2000);
    }

    async handleSignaling(msg) {
        const fromUser = msg.sender_id;
        const content = JSON.parse(msg.content);

        if (msg.type === 'offer') {
            await this.handleOffer(fromUser, content);
        } else if (msg.type === 'answer') {
            await this.handleAnswer(fromUser, content);
        } else if (msg.type === 'candidate') {
            await this.handleCandidate(fromUser, content);
        }
    }

    async initiateCall(targetUserId) {
        if (this.peers[targetUserId]) return;

        const pc = this.createPeerConnection(targetUserId);
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        await this.sendSignaling(targetUserId, 'offer', offer);
    }

    async handleOffer(fromUser, offer) {
        if (this.peers[fromUser]) {
            // Re-negotiation or duplicate? For simplicity, we recreate if needed
            this.peers[fromUser].connection.close();
        }

        const pc = this.createPeerConnection(fromUser);
        await pc.setRemoteDescription(new RTCSessionDescription(offer));
        
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);

        await this.sendSignaling(fromUser, 'answer', answer);
    }

    async handleAnswer(fromUser, answer) {
        const peer = this.peers[fromUser];
        if (peer) {
            await peer.connection.setRemoteDescription(new RTCSessionDescription(answer));
        }
    }

    async handleCandidate(fromUser, candidate) {
        const peer = this.peers[fromUser];
        if (peer) {
            await peer.connection.addIceCandidate(new RTCIceCandidate(candidate));
        }
    }

    createPeerConnection(targetUserId) {
        const pc = new RTCPeerConnection(this.iceServers);
        
        this.localStream.getTracks().forEach(track => {
            pc.addTrack(track, this.localStream);
        });

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.sendSignaling(targetUserId, 'candidate', event.candidate);
            }
        };

        pc.ontrack = (event) => {
            this.addVideoTrack(targetUserId, event.streams[0], false);
        };

        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed' || pc.connectionState === 'closed') {
                this.removeVideoTrack(targetUserId);
            }
        };

        this.peers[targetUserId] = { connection: pc };
        return pc;
    }

    async sendSignaling(receiverId, type, content) {
        const body = new FormData();
        body.append('room_id', this.roomId);
        body.append('receiver_id', receiverId);
        body.append('type', type);
        body.append('content', JSON.stringify(content));
        await api('send_signaling', 'POST', body);
    }

    addVideoTrack(userId, stream, isLocal) {
        const grid = document.getElementById('video-grid');
        let wrapper = document.getElementById(`video-wrap-${userId}`);
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.id = `video-wrap-${userId}`;
            wrapper.className = 'video-wrapper';
            
            const video = document.createElement('video');
            video.autoplay = true;
            video.playsInline = true;
            if (isLocal) video.muted = true;
            
            const label = document.createElement('div');
            label.className = 'video-label';
            label.innerText = isLocal ? '自分' : `ユーザー ${userId}`;
            
            wrapper.appendChild(video);
            wrapper.appendChild(label);
            grid.appendChild(wrapper);
        }
        
        const video = wrapper.querySelector('video');
        video.srcObject = stream;
    }

    removeVideoTrack(userId) {
        const wrapper = document.getElementById(`video-wrap-${userId}`);
        if (wrapper) wrapper.remove();
        if (this.peers[userId]) {
            delete this.peers[userId];
        }
    }

    toggleMic() {
        this.isMuted = !this.isMuted;
        this.localStream.getAudioTracks().forEach(track => track.enabled = !this.isMuted);
        document.getElementById('toggle-mic').classList.toggle('muted', this.isMuted);
    }

    toggleVideo() {
        this.isVideoOff = !this.isVideoOff;
        this.localStream.getVideoTracks().forEach(track => track.enabled = !this.isVideoOff);
        document.getElementById('toggle-video').classList.toggle('muted', this.isVideoOff);
    }

    leave() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        
        for (const userId in this.peers) {
            this.peers[userId].connection.close();
            this.removeVideoTrack(userId);
        }
        
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }
        
        this.removeVideoTrack(currentUserId);
        this.peers = {};
        this.roomId = null;
        this.lastSignalingId = 0;
        
        document.getElementById('meeting-modal').close();
    }
}

const meetingManager = new MeetingManager();

function startMeeting() {
    if (isDmMode) {
        meetingManager.start(null, currentPartnerId);
    } else {
        meetingManager.start(currentThreadId, null);
    }
}
