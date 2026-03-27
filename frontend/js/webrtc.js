/**
 * WebRTC Meeting Manager
 * Handles Mesh topology P2P communication for SYCS
 */

class MeetingManager {
  constructor() {
    this.localStream = null;
    this.peers = {}; // peerUserId -> { connection, stream }
    this.pendingCandidates = {}; // userId -> [candidate1, candidate2, ...]
    this.roomId = null;
    this.lastSignalingId = 0;
    this.isMuted = false;
    this.isVideoOff = false;
    this.isScreenSharing = false;
    this.screenStream = null;
    this.pollingInterval = null;
    this.iceServers = {
      iceServers: [
        { urls: "stun:stun.l.google.com:19302" },
        { urls: "stun:stun1.l.google.com:19302" },
      ],
    };
  }

  async init() {
    try {
      this.localStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true,
      });
      this.addVideoTrack(currentUserId, this.localStream, true);
    } catch (err) {
      console.error("Error accessing media devices:", err);
      alert("カメラまたはマイクにアクセスできませんでした。");
      throw err;
    }
  }

  async start(threadId, dmPartnerId) {
    await this.init();

    const body = new FormData();
    if (threadId) body.append("thread_id", threadId);
    if (dmPartnerId) body.append("dm_partner_id", dmPartnerId);

    const res = await api("join_meeting", "POST", body);
    if (res.error) {
      alert(res.error);
      return;
    }

    this.roomId = res.room_id;
    document.getElementById("meeting-modal").showModal();

    // Use Socket.io for signaling instead of polling
    this.setupSocketEvents();

    socket.emit("join_meeting", {
      roomId: this.roomId,
      userId: currentUserId,
      username: currentUserName,
    });

    // If it's a DM, we can automatically "knock" the partner
    // In a thread, we'll just wait for others to join and send offers
    if (dmPartnerId) {
      this.initiateCall(dmPartnerId);
    }
  }
    setupSocketEvents() {
    if (!socket) {
      console.error("Socket.io is not initialized");
      return;
    }

    socket.on("user_joined_meeting", (data) => {
      console.log("User joined meeting:", data);
      if (data.userId != currentUserId) {
        this.initiateCall(data.userId);
      }
    });

    socket.on("user_left_meeting", (data) => {
      console.log("User left meeting:", data);
      this.removeVideoTrack(data.userId);
    });

    socket.on("webrtc_signal", async (data) => {
      if (data.senderId == currentUserId) return;
      await this.handleSignaling(data);
    });
  }

  async handleSignaling(msg) {
    const fromUser = msg.senderId;
    const content = msg.content;
    // No longer stringified in new version
    if (msg.type === "offer") {
      await this.handleOffer(fromUser, content);
    } else if (msg.type === "answer") {
      await this.handleAnswer(fromUser, content);
    } else if (msg.type === "candidate") {
      await this.handleCandidate(fromUser, content);
    }
  }

  async initiateCall(targetUserId) {
    if (this.peers[targetUserId]) return;

    const pc = this.createPeerConnection(targetUserId);
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    await this.sendSignaling(targetUserId, "offer", offer);
  }

  async handleOffer(fromUser, offer) {
    if (this.peers[fromUser]) {
      // Re-negotiation or duplicate? For simplicity, we recreate if needed
      this.peers[fromUser].connection.close();
    }

    const pc = this.createPeerConnection(fromUser);
    await pc.setRemoteDescription(new RTCSessionDescription(offer));
    await this.flushPendingCandidates(fromUser);

    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);

    await this.sendSignaling(fromUser, "answer", answer);
  }

  async handleAnswer(fromUser, answer) {
    const peer = this.peers[fromUser];
    if (peer) {
      await peer.connection.setRemoteDescription(
        new RTCSessionDescription(answer),
      );
      await this.flushPendingCandidates(fromUser);
    }
  }

  async handleCandidate(fromUser, candidate) {
    const peer = this.peers[fromUser];
    if (peer && peer.connection.remoteDescription) {
      await peer.connection.addIceCandidate(new RTCIceCandidate(candidate));
    } else {
      if (!this.pendingCandidates[fromUser]) {
        this.pendingCandidates[fromUser] = [];
      }
      this.pendingCandidates[fromUser].push(candidate);
    }
  }

  createPeerConnection(targetUserId) {
    const pc = new RTCPeerConnection(this.iceServers);

    this.localStream.getTracks().forEach((track) => {
      pc.addTrack(track, this.localStream);
    });

    pc.onicecandidate = (event) => {
      if (event.candidate) {
        this.sendSignaling(targetUserId, "candidate", event.candidate);
      }
    };

    pc.ontrack = (event) => {
      const stream = event.streams[0] || new MediaStream([event.track]);
      this.addVideoTrack(targetUserId, stream, false);
    };

    pc.onconnectionstatechange = () => {
      if (
        pc.connectionState === "disconnected" ||
        pc.connectionState === "failed" ||
        pc.connectionState === "closed"
      ) {
        this.removeVideoTrack(targetUserId);
      }
    };

    this.peers[targetUserId] = { connection: pc };
    return pc;
  }

  async sendSignaling(receiverId, type, content) {
      if (socket) {
      socket.emit("webrtc_signal", {
        roomId: this.roomId,
        targetId: receiverId,
        senderId: currentUserId,
        type: type,
        content: content,
      });
    } else {
      // Fallback to legacy API if socket is unavailable (optional)
      const body = new FormData();
      body.append("room_id", this.roomId);
      body.append("receiver_id", receiverId);
      body.append("type", type);
      body.append("content", JSON.stringify(content));
      await api("send_signaling", "POST", body);
    }
  }

  async flushPendingCandidates(userId) {
const peer = this.peers[userId];
const candidates = this.pendingCandidates[userId] || [];

if (!peer || !peer.connection.remoteDescription) {
  return;
}

while (queued.length > 0) {
  const candidate = queued.shift();
  await peer.connection.addIceCandidate(new RTCIceCandidate(candidate));
}

delete this.pendingCandidates[userId];
  }

  addVideoTrack(userId, stream, isLocal) {
    const grid = document.getElementById("video-grid");
    let wrapper = document.getElementById(`video-wrap-${userId}`);

    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.id = `video-wrap-${userId}`;
      wrapper.className = "video-wrapper";

      const video = document.createElement("video");
      video.autoplay = true;
      video.muted = isLocal;
      video.setAttribute("playsinline", "");
      video.playsInline = true;

      const label = document.createElement("div");
      label.className = "video-label";
      label.innerText = isLocal ? "自分" : `ユーザー ${userId}`;

      wrapper.appendChild(video);
      wrapper.appendChild(label);
      grid.appendChild(wrapper);
    }

    const video = wrapper.querySelector("video");
    if (video.srcObject !== stream) {
      video.srcObject = stream;
      video.play().catch((err) => {
        console.warn(
          "Video play failed,可能は自動再生ポリシーによる制限です:",
          err,
        );
      });
    }
  }

  removeVideoTrack(userId) {
    const wrapper = document.getElementById(`video-wrap-${userId}`);
    if (wrapper) wrapper.remove();
    if (this.peers[userId]) {
      delete this.peers[userId];
    }
    if (this.pendingCandidates[userId]) {
      delete this.pendingCandidates[userId];
    }
  }

  toggleMic() {
    this.isMuted = !this.isMuted;
    this.localStream
      .getAudioTracks()
      .forEach((track) => (track.enabled = !this.isMuted));
    const micBtn = document.getElementById("toggle-mic");
    const micIcon = document.getElementById("mic-icon");
    micBtn.classList.toggle("muted", this.isMuted);
    if (micIcon) {
      micIcon.src = this.isMuted
        ? "assets/img/mic_muted.svg"
        : "assets/img/mic.svg";
    }
  }

  toggleVideo() {
    this.isVideoOff = !this.isVideoOff;
    this.localStream
      .getVideoTracks()
      .forEach((track) => (track.enabled = !this.isVideoOff));
    const videoBtn = document.getElementById("toggle-video");
    const videoIcon = document.getElementById("video-icon");
    videoBtn.classList.toggle("muted", this.isVideoOff);
    if (videoIcon) {
      videoIcon.src = this.isVideoOff
        ? "assets/img/camera_off.svg"
        : "assets/img/camera_on.svg";
    }
  }

  async toggleScreenShare() {
    if (this.isScreenSharing) {
      this.stopScreenShare();
    } else {
      await this.startScreenShare();
    }
  }

  async startScreenShare() {
    try {
      this.screenStream = await navigator.mediaDevices.getDisplayMedia({
        video: true,
      });
      this.isScreenSharing = true;

      const screenTrack = this.screenStream.getVideoTracks()[0];

      // Replace track for all peers
      for (const userId in this.peers) {
        const pc = this.peers[userId].connection;
        const senders = pc.getSenders();
        const videoSender = senders.find(
          (s) => s.track && s.track.kind === "video",
        );
        if (videoSender) {
          videoSender.replaceTrack(screenTrack);
        }
      }

      // Update local preview
      const localVideo = document.querySelector(
        `#video-wrap-${currentUserId} video`,
      );
      if (localVideo) {
        localVideo.srcObject = this.screenStream;
      }

      // Handle manual stop (browser button)
      screenTrack.onended = () => {
        this.stopScreenShare();
      };

      document.getElementById("toggle-screen").classList.add("active");
    } catch (err) {
      console.error("Error starting screen share:", err);
    }
  }

  stopScreenShare() {
    if (!this.isScreenSharing) return;

    if (this.screenStream) {
      this.screenStream.getTracks().forEach((track) => track.stop());
      this.screenStream = null;
    }
    this.isScreenSharing = false;

    const videoTrack = this.localStream.getVideoTracks()[0];

    // Restore camera track for all peers
    for (const userId in this.peers) {
      const pc = this.peers[userId].connection;
      const senders = pc.getSenders();
      const videoSender = senders.find(
        (s) => s.track && s.track.kind === "video",
      );
      if (videoSender) {
        videoSender.replaceTrack(videoTrack);
      }
    }

    // Restore local preview
    const localVideo = document.querySelector(
      `#video-wrap-${currentUserId} video`,
    );
    if (localVideo) {
      localVideo.srcObject = this.localStream;
    }

    document.getElementById("toggle-screen").classList.remove("active");
  }

  leave() {
    if (this.roomId && socket) {
      socket.emit("leave_meeting", {
        roomId: this.roomId,
        userId: currentUserId,
      });
    }
    for (const userId in this.peers) {
      this.peers[userId].connection.close();
      this.removeVideoTrack(userId);
    }

    if (this.localStream) {
      this.localStream.getTracks().forEach((track) => track.stop());
    }

    this.removeVideoTrack(currentUserId);
    this.peers = {};
    this.roomId = null;
    this.lastSignalingId = 0;

    document.getElementById("meeting-modal").close();
    // Remove socket listeners to avoid duplicates on next start
    if (socket) {
      socket.off("user_joined_meeting");
      socket.off("user_left_meeting");
      socket.off("webrtc_signal");
    }
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
