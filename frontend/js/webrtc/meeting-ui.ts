/**
 * MeetingUI
 * Manages video tiles, participant status indicators, and meeting controls UI.
 */
class MeetingUI {
    constructor(containerId = 'video-grid') {
        this.containerId = containerId;
    }

    getGridContainer() {
        return document.getElementById(this.containerId);
    }

    addOrUpdateTile(peerId, stream, isLocal = false, labelText = null) {
        const grid = this.getGridContainer();
        if (!grid) return;

        let wrap = document.getElementById(`v-wrap-${peerId}`);
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = `v-wrap-${peerId}`;
            wrap.className = 'video-wrapper';

            const video = document.createElement('video');
            video.autoplay = true;
            video.muted = isLocal;
            video.setAttribute('playsinline', '');
            video.playsInline = true;

            const label = document.createElement('div');
            label.className = 'video-label';
            label.innerText = isLocal ? '自分' : (labelText || `参加者 ${peerId}`);

            const statusBadge = document.createElement('div');
            statusBadge.className = 'status-badge connected';
            statusBadge.id = `status-badge-${peerId}`;
            statusBadge.innerText = '接続済み';

            const videoOffOverlay = document.createElement('div');
            videoOffOverlay.className = 'video-off-overlay';
            videoOffOverlay.id = `v-off-overlay-${peerId}`;
            videoOffOverlay.style.cssText = 'position:absolute; top:0; left:0; width:100%; height:100%; background:#0f172a; display:none; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; z-index:2;';
            videoOffOverlay.innerHTML = '<img src="assets/img/camera_off.svg" style="width:48px; height:48px; opacity:0.6; filter:invert(1); margin-bottom:8px;"><span style="font-size:0.85rem;">カメラOFF</span>';

            wrap.appendChild(video);
            wrap.appendChild(videoOffOverlay);
            wrap.appendChild(label);
            wrap.appendChild(statusBadge);
            grid.appendChild(wrap);
        }

        const video = wrap.querySelector('video');
        if (stream && video.srcObject !== stream) {
            video.srcObject = stream;
            video.play().catch(err => console.warn(`[MeetingUI] Autoplay issue for ${peerId}:`, err));
        }
    }

    setCameraOffOverlay(peerId, isOff) {
        const overlay = document.getElementById(`v-off-overlay-${peerId}`);
        if (overlay) {
            overlay.style.display = isOff ? 'flex' : 'none';
        }
    }

    updatePeerState(peerId, state) {
        const badge = document.getElementById(`status-badge-${peerId}`);
        if (!badge) return;

        badge.className = `status-badge ${state}`;
        const stateLabels = {
            'connecting': '接続中...',
            'connected': '接続済み',
            'disconnected': '切断',
            'failed': '接続失敗',
            'closed': '終了'
        };
        badge.innerText = stateLabels[state] || state;
    }

    removeTile(peerId) {
        const wrap = document.getElementById(`v-wrap-${peerId}`);
        if (wrap) wrap.remove();
    }

    updateMicButton(isMuted) {
        const micBtn = document.getElementById('toggle-mic');
        const micIcon = document.getElementById('mic-icon');
        if (micBtn) micBtn.classList.toggle('muted', isMuted);
        if (micIcon) {
            micIcon.src = isMuted ? 'assets/img/mic_muted.svg' : 'assets/img/mic.svg';
        }
    }

    updateCameraButton(isVideoOff) {
        const cameraBtn = document.getElementById('toggle-video');
        const cameraIcon = document.getElementById('video-icon');
        if (cameraBtn) cameraBtn.classList.toggle('muted', isVideoOff);
        if (cameraIcon) {
            cameraIcon.src = isVideoOff ? 'assets/img/camera_off.svg' : 'assets/img/camera_on.svg';
        }
    }

    updateScreenButton(isSharing) {
        const screenBtn = document.getElementById('toggle-screen');
        if (screenBtn) screenBtn.classList.toggle('active', isSharing);
    }

    showModal() {
        const modal = document.getElementById('meeting-modal');
        if (modal && typeof modal.showModal === 'function') {
            modal.showModal();
        } else if (modal) {
            modal.style.display = 'block';
        }
    }

    closeModal() {
        const modal = document.getElementById('meeting-modal');
        if (modal && typeof modal.close === 'function') {
            modal.close();
        } else if (modal) {
            modal.style.display = 'none';
        }
        const grid = this.getGridContainer();
        if (grid) grid.innerHTML = '';
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = MeetingUI;
}
