/**
 * MediaManager
 * Responsible for managing camera, microphone, screen sharing, and audio-only fallback.
 */
class MediaManager {
    constructor() {
        this.localStream = null;
        this.screenStream = null;
        this.isMuted = false;
        this.isVideoOff = false;
        this.isScreenSharing = false;
        this.isAudioOnly = false;
        this.onScreenShareEndedCallback = null;
    }

    async acquireLocalMedia(preferVideo = true) {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: preferVideo,
                audio: true
            });
            this.isAudioOnly = !preferVideo || this.localStream.getVideoTracks().length === 0;
            return this.localStream;
        } catch (err) {
            console.warn('[MediaManager] Failed to acquire video+audio, trying audio-only fallback:', err);
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    video: false,
                    audio: true
                });
                this.isAudioOnly = true;
                return this.localStream;
            } catch (audioErr) {
                console.error('[MediaManager] Failed to acquire audio device:', audioErr);
                throw audioErr;
            }
        }
    }

    toggleMic() {
        if (!this.localStream) return this.isMuted;
        this.isMuted = !this.isMuted;
        this.localStream.getAudioTracks().forEach(t => t.enabled = !this.isMuted);
        return this.isMuted;
    }

    async toggleCamera() {
        if (!this.localStream) return this.isVideoOff;

        const videoTracks = this.localStream.getVideoTracks();
        if (videoTracks.length === 0) {
            // Started in audio-only mode, attempt to request camera stream now
            try {
                const vidStream = await navigator.mediaDevices.getUserMedia({ video: true });
                const newVideoTrack = vidStream.getVideoTracks()[0];
                if (newVideoTrack) {
                    this.localStream.addTrack(newVideoTrack);
                    this.isAudioOnly = false;
                    this.isVideoOff = false;
                    return false; // Camera is now ON
                }
            } catch (err) {
                console.warn('[MediaManager] Failed to acquire camera stream on toggle:', err);
                this.isAudioOnly = true;
                this.isVideoOff = true;
                return true;
            }
        }

        this.isVideoOff = !this.isVideoOff;
        this.localStream.getVideoTracks().forEach(t => t.enabled = !this.isVideoOff);
        return this.isVideoOff;
    }

    async startScreenShare() {
        if (this.isScreenSharing) return this.screenStream;
        try {
            this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: true
            });
            this.isScreenSharing = true;

            const screenTrack = this.screenStream.getVideoTracks()[0];
            if (screenTrack) {
                screenTrack.onended = () => {
                    this.stopScreenShare();
                    if (typeof this.onScreenShareEndedCallback === 'function') {
                        this.onScreenShareEndedCallback();
                    }
                };
            }
            return this.screenStream;
        } catch (err) {
            console.error('[MediaManager] Screen share canceled or failed:', err);
            throw err;
        }
    }

    stopScreenShare() {
        if (!this.isScreenSharing) return;
        if (this.screenStream) {
            this.screenStream.getTracks().forEach(t => t.stop());
            this.screenStream = null;
        }
        this.isScreenSharing = false;
    }

    getActiveVideoStream() {
        if (this.isScreenSharing && this.screenStream) {
            return this.screenStream;
        }
        return this.localStream;
    }

    stopAllMedia() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(t => t.stop());
            this.localStream = null;
        }
        if (this.screenStream) {
            this.screenStream.getTracks().forEach(t => t.stop());
            this.screenStream = null;
        }
        this.isMuted = false;
        this.isVideoOff = false;
        this.isScreenSharing = false;
        this.isAudioOnly = false;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = MediaManager;
}
