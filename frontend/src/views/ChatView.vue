<template>
  <div class="chat-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2 class="sycs-logo">SYCS</h2>
        <div class="user-info" v-if="authStore.user" @click="openProfileModal" title="Edit Profile">
          <div class="avatar">{{ authStore.user.username.charAt(0).toUpperCase() }}</div>
          <span>{{ authStore.user.username }}</span>
        </div>
      </div>

      <div class="search-bar">
        <input 
          v-model="searchKeyword" 
          @keyup.enter="executeSearch" 
          placeholder="Search messages..." 
          class="search-input"
        />
        <button class="btn-icon" @click="executeSearch" title="Search">🔍</button>
      </div>

      <div class="thread-list">
        <div class="thread-list-header">
          <h3>Channels</h3>
          <button @click="isCreatingThread = true" class="btn-icon" title="New Channel">+</button>
        </div>
        
        <div v-if="isCreatingThread" class="new-thread-form">
          <input 
            v-model="newThreadTitle" 
            @keyup.enter="createThread"
            placeholder="Channel name..." 
            autoFocus
          />
          <div class="new-thread-actions">
            <button class="btn-text" @click="isCreatingThread = false">Cancel</button>
            <button class="btn-text" @click="createThread" :disabled="!newThreadTitle.trim()">Create</button>
          </div>
        </div>

        <ul class="threads">
          <li 
            v-for="thread in threads" 
            :key="thread.id" 
            :class="{ active: currentThread && currentThread.id === thread.id }"
            @click="selectThread(thread)"
            class="thread-item"
          >
            <div v-if="editingThreadId === thread.id" class="edit-thread-form" @click.stop>
              <input v-model="editingThreadTitle" @keyup.enter="saveThreadEdit(thread)" @keyup.esc="cancelThreadEdit" class="edit-input-small" autoFocus />
              <div class="thread-actions">
                <button @click="saveThreadEdit(thread)" class="btn-text btn-save" title="Save">✔️</button>
                <button @click="cancelThreadEdit" class="btn-text" title="Cancel">❌</button>
              </div>
            </div>
            <div v-else class="thread-content-wrapper">
              <span><span class="hash">#</span> {{ thread.title }}</span>
              <span v-if="unreadCounts[thread.id] && (!currentThread || currentThread.id !== thread.id)" class="unread-badge">
                {{ unreadCounts[thread.id] > 99 ? '99+' : unreadCounts[thread.id] }}
              </span>
              <div class="thread-hover-actions" v-if="authStore.user && thread.creator_name === authStore.user.username">
                <button @click.stop="startThreadEdit(thread)" class="btn-icon-small" title="Edit">✎</button>
                <button @click.stop="deleteThread(thread.id)" class="btn-icon-small btn-danger" title="Delete">🗑</button>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <template v-if="pendingRequests.length > 0">
        <div class="thread-list-header mt-4">
          <h3>Friend Requests</h3>
        </div>
        <ul class="threads">
          <li v-for="req in pendingRequests" :key="req.request_id" class="thread-item">
            <div class="thread-content-wrapper">
              <span class="user-name-request">{{ req.username }}</span>
              <div class="thread-hover-actions" style="opacity: 1">
                <button @click="handleFriendRequest(req.request_id, 'accept')" class="btn-icon-small" title="Accept">✔️</button>
                <button @click="handleFriendRequest(req.request_id, 'reject')" class="btn-icon-small btn-danger" title="Reject">❌</button>
              </div>
            </div>
          </li>
        </ul>
      </template>

      <div class="thread-list-header mt-4">
        <h3>Direct Messages</h3>
      </div>
      <ul class="threads">
        <li 
          v-for="partner in dmPartners" 
          :key="partner.id" 
          :class="{ active: currentDM && currentDM.id === partner.id }"
          @click="selectDM(partner)"
          class="thread-item"
        >
          <div class="thread-content-wrapper">
            <span><span class="status-indicator-small" :class="partner.status || 'offline'"></span> {{ partner.username }}</span>
          </div>
        </li>
      </ul>

      <div class="user-list">
        <div class="user-list-header">
          <h3>Members</h3>
        </div>
        <ul class="users">
          <li v-for="u in users" :key="u.id" class="user-item">
            <span class="status-indicator" :class="u.status || 'offline'"></span>
            <span class="user-name clickable" @click="selectDM(u)">{{ u.username }}</span>
            <span v-if="u.custom_status" class="custom-status" :title="u.custom_status">💬</span>
            <button 
              v-if="authStore.user && u.id !== authStore.user.id" 
              @click="sendFriendRequest(u.id)" 
              class="btn-icon-small ml-auto" 
              title="Add Friend"
            >👤+</button>
          </li>
        </ul>
      </div>

      <div class="sidebar-footer">
        <button @click="handleLogout" class="btn-logout">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Logout
        </button>
      </div>
    </aside>

    <main class="chat-area">
      <div v-if="globalError" class="global-error-banner">
        {{ globalError }}
      </div>

      <template v-if="currentThread || currentDM">
        <div class="chat-header">
          <div class="header-main">
            <h2 v-if="currentThread"><span class="hash">#</span> {{ currentThread.title }}</h2>
            <h2 v-else>@ {{ currentDM.username }}</h2>
            <span class="creator-info" v-if="currentThread">Created by {{ currentThread.creator_name }}</span>
            <span class="creator-info" v-else>{{ currentDM.status }} - {{ currentDM.custom_status || 'Direct Message' }}</span>
          </div>
          <div class="header-actions" v-if="currentThread">
            <button @click="initiateMeeting" class="btn-primary btn-sm" title="Start Video Call">
              📹 Video Call
            </button>
          </div>
        </div>

        <div class="message-list" ref="messageListRef">
          <div v-if="messages.length === 0" class="empty-state">
            {{ currentThread ? `Start the conversation in #${currentThread.title}` : `This is the beginning of your direct message history with @${currentDM.username}` }}
          </div>
          <div 
            v-for="(msg, index) in messages" 
            :key="msg.id" 
            class="message-item"
            :class="{ 'mt-4': index === 0 || messages[index-1].username !== msg.username }"
          >
            <div class="message-avatar" v-if="index === 0 || messages[index-1].username !== msg.username">
              {{ msg.username.charAt(0).toUpperCase() }}
            </div>
            <div class="message-spacer" v-else></div>
            
            <div class="message-content">
              <div class="message-meta" v-if="index === 0 || messages[index-1].username !== msg.username">
                <span class="message-author">{{ msg.username }}</span>
                <span class="message-time">{{ formatDate(msg.created_at) }}</span>
                <span v-if="msg.is_pinned" class="pinned-badge" title="Pinned">📌</span>
              </div>
              <div class="message-text" :class="{ 'is-pinned': msg.is_pinned }">
                <div v-if="editingMessageId === msg.id" class="edit-message-form">
                  <input 
                    v-model="editingContent" 
                    @keyup.enter="saveEdit(msg)" 
                    @keyup.esc="cancelEdit" 
                    class="edit-input" 
                    autoFocus 
                  />
                  <div class="edit-actions">
                    <button @click="saveEdit(msg)" class="btn-text btn-save">Save</button>
                    <button @click="cancelEdit" class="btn-text">Cancel</button>
                  </div>
                </div>
                <div v-else>
                  <div class="msg-content-text">{{ msg.content }}</div>
                  <div v-if="msg.attachment_path" class="message-attachment">
                    <img v-if="isImage(msg.attachment_path)" :src="msg.attachment_path" class="attachment-image" />
                    <a v-else :href="msg.attachment_path" target="_blank" class="attachment-file">📎 Download Attachment</a>
                  </div>
                </div>
              </div>
              
              <div class="reactions-list" v-if="msg.reactions && msg.reactions.length > 0">
                <button 
                  v-for="grp in groupReactions(msg.reactions)" 
                  :key="grp.emoji"
                  class="reaction-badge"
                  :title="grp.title"
                  @click="toggleReaction(msg.id, grp.emoji)"
                >
                  {{ grp.emoji }} <span class="reaction-count">{{ grp.count }}</span>
                </button>
              </div>
            </div>

            <div class="message-actions">
              <button v-if="authStore.user" @click="toggleReaction(msg.id, '👍')" class="btn-action" title="React 👍">👍</button>
              <button v-if="authStore.user" @click="toggleReaction(msg.id, '❤️')" class="btn-action" title="React ❤️">❤️</button>
              <button v-if="authStore.user" @click="togglePin(msg.id)" class="btn-action" :title="msg.is_pinned ? 'Unpin' : 'Pin'">📌</button>
              
              <template v-if="authStore.user && authStore.user.username === msg.username && editingMessageId !== msg.id">
                <button @click="startEdit(msg)" class="btn-action" title="Edit">✎</button>
                <button @click="deleteMessage(msg.id)" class="btn-action btn-danger" title="Delete">🗑</button>
              </template>
            </div>
          </div>
        </div>

        <div class="chat-input-area">
          <div v-if="typingUsers.length > 0" class="typing-indicator-chat">
            <span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>
            {{ typingUsers.join(', ') }} {{ typingUsers.length > 1 ? 'are' : 'is' }} typing
          </div>
          <div v-if="selectedFile" class="file-preview">
            <span>📎 {{ selectedFile.name }}</span>
            <button type="button" @click="clearFile" class="btn-icon">❌</button>
          </div>
          <form @submit.prevent="sendMessage" class="message-form">
            <label class="file-upload-btn" title="Attach file">
              📎
              <input type="file" @change="handleFileUpload" style="display: none;" />
            </label>
            <input 
              type="text" 
              v-model="newMessage" 
              @input="notifyTyping"
              :placeholder="currentThread ? `Message #${currentThread.title}` : `Message @${currentDM.username}`" 
              autocomplete="off"
            />
            <button type="submit" :disabled="(!newMessage.trim() && !selectedFile) || isSending" class="btn-send">
              <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
          </form>
        </div>
      </template>

      <div v-else class="no-thread-selected">
        <div class="no-thread-content">
          <div class="brand-huge">SYCS</div>
          <p>Select a channel or create a new one to start chatting.</p>
        </div>
      </div>
    </main>

    <!-- Search Results Modal -->
    <div v-if="showSearchModal" class="modal-overlay" @click="closeSearchModal">
      <div class="modal-content search-modal-content" @click.stop>
        <div class="modal-header">
          <h3>Search Results for "{{ lastSearchKeyword }}"</h3>
          <button @click="closeSearchModal" class="btn-icon">❌</button>
        </div>
        <div class="modal-body search-results-body">
          <div v-if="isSearching" class="text-center">Searching...</div>
          <div v-else-if="searchResults.length === 0" class="text-center">No results found.</div>
          <div class="search-result-item" v-for="res in searchResults" :key="res.id" @click="jumpToResult(res)">
            <div class="search-result-meta">
              <span class="search-result-author">{{ res.author_name }}</span>
              <span class="search-result-thread">in #{{ res.thread_title }}</span>
              <span class="search-result-time">{{ formatDate(res.created_at) }}</span>
            </div>
            <div class="search-result-content">{{ res.content }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Modal Overlays -->
    <div v-if="showProfileModal" class="modal-overlay" @click="closeProfileModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Profile Settings</h3>
          <button @click="closeProfileModal" class="btn-icon">❌</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Status</label>
            <select v-model="profileForm.status" class="edit-input">
              <option value="online">Online</option>
              <option value="busy">Busy</option>
              <option value="away">Away</option>
              <option value="offline">Offline</option>
            </select>
          </div>
          <div class="form-group mt-2">
            <label>Custom Status</label>
            <input v-model="profileForm.custom_status" class="edit-input" placeholder="What's on your mind?" />
          </div>
          <div class="form-group mt-2">
            <label>Bio</label>
            <textarea v-model="profileForm.bio" class="edit-input" rows="3" placeholder="Tell us about yourself..."></textarea>
          </div>
          <div class="form-group mt-2">
            <label>Social Links</label>
            <div class="social-links-grid">
              <div class="social-item">
                <span class="social-icon">Discord</span>
                <input v-model="profileForm.social_links.discord" class="edit-input-small" placeholder="user#1234" />
              </div>
              <div class="social-item">
                <span class="social-icon">GitHub</span>
                <input v-model="profileForm.social_links.github" class="edit-input-small" placeholder="Username" />
              </div>
              <div class="social-item">
                <span class="social-icon">Twitter</span>
                <input v-model="profileForm.social_links.twitter" class="edit-input-small" placeholder="@username" />
              </div>
            </div>
          </div>
          <div class="form-group mt-2">
            <label>Banner Color</label>
            <input type="color" v-model="profileForm.banner_color" class="edit-input" />
          </div>
        </div>
        <div class="modal-footer">
          <span v-if="profileSaveError" class="text-danger">{{ profileSaveError }}</span>
          <button class="btn-primary" @click="saveProfile">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Meeting Overlay -->
    <div v-if="activeMeeting" class="meeting-overlay">
      <div class="meeting-header">
        <div class="meeting-info-top">
          <h4>Meeting in #{{ currentThread?.title }}</h4>
          <div class="id-pass">ID: {{ activeMeeting.meeting_id }} | PASS: {{ activeMeeting.password }}</div>
        </div>
        <button @click="leaveMeeting" class="btn-danger btn-sm">Leave</button>
      </div>

      <div class="video-grid" id="video-grid">
        <div class="video-wrapper local-video">
          <video ref="localVideoRef" autoplay muted playsinline></video>
          <div class="video-label">You ({{ authStore.user?.username }})</div>
        </div>
        <div v-for="(peer, uid) in peers" :key="uid" class="video-wrapper remote-video">
          <video :ref="el => { if(el) remoteVideoRefs[uid] = el }" autoplay playsinline></video>
          <div class="video-label">{{ peer.username }}</div>
        </div>
      </div>

      <div class="meeting-controls">
        <button @click="toggleMic" :class="{ 'btn-muted': isMuted }" class="control-btn" title="Toggle Mic">
          {{ isMuted ? '🔇' : '🎤' }}
        </button>
        <button @click="toggleVideo" :class="{ 'btn-muted': isVideoOff }" class="control-btn" title="Toggle Video">
          {{ isVideoOff ? '📷' : '📹' }}
        </button>
        <button @click="toggleScreenShare" :class="{ 'btn-active': isScreenSharing }" class="control-btn" title="Screen Share">
          🖥️
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import '../assets/chat.css';

const authStore = useAuthStore();
const router = useRouter();

const threads = ref([]);
const users = ref([]);
const unreadCounts = ref({});
const currentThread = ref(null);
const currentDM = ref(null);
const messages = ref([]);
const newMessage = ref('');

const dmPartners = ref([]);
const friendsList = ref([]);
const pendingRequests = ref([]);

// Meeting State
const activeMeeting = ref(null);
const localStream = ref(null);
const localVideoRef = ref(null);
const remoteVideoRefs = ref({});
const peers = ref({}); // { userId: { pc, username, stream } }
const lastSignalId = ref(0);
const signalPolling = ref(null);
const isMuted = ref(false);
const isVideoOff = ref(false);
const isScreenSharing = ref(false);
const screenStream = ref(null);
const iceServers = { iceServers: [{ urls: "stun:stun.l.google.com:19302" }] };

const searchKeyword = ref('');
const lastSearchKeyword = ref('');
const showSearchModal = ref(false);
const isSearching = ref(false);
const searchResults = ref([]);

const typingUsers = ref([]);
const lastTypingNotify = ref(0);

const selectedFile = ref(null);

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (file) selectedFile.value = file;
};

const clearFile = () => {
  selectedFile.value = null;
};

const isImage = (path) => {
  if (!path) return false;
  return /\.(jpg|jpeg|png|gif|webp)$/i.test(path);
};


const showProfileModal = ref(false);
const profileForm = ref({ status: 'online', custom_status: '', bio: '', banner_color: '#6366f1' });
const profileSaveError = ref('');

const openProfileModal = async () => {
  showProfileModal.value = true;
  profileSaveError.value = '';
  try {
    const data = await safeFetch('/api/profile.php');
    if (data.success) {
      profileForm.value = {
        status: data.profile.status || 'online',
        custom_status: data.profile.custom_status || '',
        bio: data.profile.bio || '',
        banner_color: data.profile.banner_color || '#6366f1',
        social_links: data.profile.social_links ? JSON.parse(data.profile.social_links) : { discord: '', github: '', twitter: '' }
      };
    }
  } catch (e) {
    profileSaveError.value = "Failed to load profile";
  }
};

const closeProfileModal = () => {
  showProfileModal.value = false;
};

const saveProfile = async () => {
  profileSaveError.value = '';
  try {
    const data = await safeFetch('/api/profile.php', {
      method: 'PUT',
      body: JSON.stringify(profileForm.value)
    });
    if (data.success) {
      closeProfileModal();
    } else {
      profileSaveError.value = data.error || "Failed to update profile";
    }
  } catch (e) {
    profileSaveError.value = e.message;
  }
};
const messageListRef = ref(null);

const editingMessageId = ref(null);
const editingContent = ref('');

const editingThreadId = ref(null);
const editingThreadTitle = ref('');

const isCreatingThread = ref(false);
const newThreadTitle = ref('');
const isSending = ref(false);
const globalError = ref('');

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const safeFetch = async (url, options = {}) => {
  const defaultHeaders = {
    'Content-Type': 'application/json'
  };
  const finalOptions = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers
    }
  };
  try {
    const res = await fetch(url, finalOptions);
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error(`サーバーエラー: 不正な応答 (${res.status}) - ${text.substring(0, 100)}`);
    }
    return data;
  } catch (err) {
    if (err.name === 'TypeError') {
      throw new Error("通信エラー: PHPサーバーが稼働しているか確認してください。");
    }
    throw err;
  }
};

const loadUnreadCounts = async () => {
  try {
    const data = await safeFetch('/api/unread_counts.php');
    if (data.success) {
      unreadCounts.value = data.counts || {};
    }
  } catch (e) {
    // silent fail
  }
};

const markAsRead = async (threadId) => {
  if (!threadId) return;
  try {
    await safeFetch('/api/read_receipt.php', {
      method: 'POST',
      body: JSON.stringify({ thread_id: threadId })
    });
    unreadCounts.value[threadId] = 0;
  } catch (e) {
    // silent fail
  }
};

const loadUsers = async () => {
  try {
    const data = await safeFetch('/api/users.php');
    if (data.success) {
      users.value = data.users;
    }
  } catch (e) {
    console.error("Failed to load users", e);
  }
};

const loadThreads = async () => {
  try {
    const data = await safeFetch('/api/threads.php');
    if (data.success) {
      threads.value = data.threads;
      globalError.value = '';
    } else {
      globalError.value = data.error || "Failed to load threads";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const createThread = async () => {
  if (!newThreadTitle.value.trim()) return;
  try {
    const data = await safeFetch('/api/threads.php', {
      method: 'POST',
      body: JSON.stringify({ title: newThreadTitle.value })
    });
    if (data.success) {
      newThreadTitle.value = '';
      isCreatingThread.value = false;
      await loadThreads();
      const fullThread = threads.value.find(t => t.id === data.thread.id);
      selectThread(fullThread || data.thread);
      globalError.value = '';
    } else {
      globalError.value = data.error || "Failed to create thread";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const startThreadEdit = (thread) => {
  editingThreadId.value = thread.id;
  editingThreadTitle.value = thread.title;
};

const cancelThreadEdit = () => {
  editingThreadId.value = null;
  editingThreadTitle.value = '';
};

const saveThreadEdit = async (thread) => {
  if (!editingThreadTitle.value.trim() || editingThreadTitle.value === thread.title) {
    cancelThreadEdit();
    return;
  }
  globalError.value = '';
  try {
    const data = await safeFetch('/api/threads.php', {
      method: 'PUT',
      body: JSON.stringify({
        thread_id: thread.id,
        title: editingThreadTitle.value
      })
    });
    if (data.success) {
      if (currentThread.value && currentThread.value.id === thread.id) {
        currentThread.value.title = editingThreadTitle.value;
      }
      cancelThreadEdit();
      await loadThreads();
    } else {
      globalError.value = data.error || "Failed to edit thread";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const deleteThread = async (threadId) => {
  if (!confirm('チャンネルを削除してよろしいですか？')) return;
  globalError.value = '';
  try {
    const data = await safeFetch('/api/threads.php', {
      method: 'DELETE',
      body: JSON.stringify({ thread_id: threadId })
    });
    if (data.success) {
      if (currentThread.value && currentThread.value.id === threadId) {
        currentThread.value = null;
        messages.value = [];
      }
      await loadThreads();
    } else {
      globalError.value = data.error || "Failed to delete thread";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const executeSearch = async () => {
  if (!searchKeyword.value.trim()) return;
  lastSearchKeyword.value = searchKeyword.value;
  showSearchModal.value = true;
  isSearching.value = true;
  searchResults.value = [];
  try {
    const data = await safeFetch(`/api/search.php?keyword=${encodeURIComponent(searchKeyword.value)}`);
    if (data.success) {
      searchResults.value = data.results;
    }
  } catch (e) {
    globalError.value = "Search failed: " + e.message;
  } finally {
    isSearching.value = false;
  }
};

const closeSearchModal = () => {
  showSearchModal.value = false;
};

const jumpToResult = async (res) => {
  const foundThread = threads.value.find(t => t.id === res.thread_id);
  if (foundThread) {
    await selectThread(foundThread);
  }
  closeSearchModal();
};

const selectThread = async (thread) => {
  currentDM.value = null;
  currentThread.value = thread;
  await loadMessages();
  await markAsRead(thread.id);
};

const loadMessages = async () => {
  if (!currentThread.value && !currentDM.value) return;
  const url = currentThread.value 
    ? `/api/messages.php?thread_id=${currentThread.value.id}`
    : `/api/dm.php?partner_id=${currentDM.value.id}`;
  try {
    const data = await safeFetch(url);
    if (data.success) {
      const isNewMessages = messages.value.length !== data.messages.length;
      messages.value = data.messages;
      globalError.value = '';
      if (isNewMessages) {
        scrollToBottom();
        if (currentThread.value) markAsRead(currentThread.value.id);
      }
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const startEdit = (msg) => {
  editingMessageId.value = msg.id;
  editingContent.value = msg.content;
};

const cancelEdit = () => {
  editingMessageId.value = null;
  editingContent.value = '';
};

const saveEdit = async (msg) => {
  if (!editingContent.value.trim() || editingContent.value === msg.content) {
    cancelEdit();
    return;
  }
  globalError.value = '';
  try {
    const data = await safeFetch('/api/messages.php', {
      method: 'PUT',
      body: JSON.stringify({
        message_id: msg.id,
        content: editingContent.value
      })
    });
    if (data.success) {
      cancelEdit();
      await loadMessages();
    } else {
      globalError.value = data.error || "Failed to edit message";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const togglePin = async (msgId) => {
  try {
    const data = await safeFetch('/api/pin.php', {
      method: 'POST',
      body: JSON.stringify({ message_id: msgId })
    });
    if (data.success) {
      await loadMessages();
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const toggleReaction = async (msgId, emoji) => {
  try {
    const data = await safeFetch('/api/reactions.php', {
      method: 'POST',
      body: JSON.stringify({ message_id: msgId, emoji })
    });
    if (data.success) {
      await loadMessages();
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const groupReactions = (reactions) => {
  if (!reactions) return [];
  const grouped = {};
  reactions.forEach(r => {
    if (!grouped[r.emoji]) grouped[r.emoji] = { count: 0, users: [] };
    grouped[r.emoji].count++;
    grouped[r.emoji].users.push(r.username);
  });
  return Object.entries(grouped).map(([emoji, data]) => ({
    emoji,
    count: data.count,
    title: data.users.join(', ')
  }));
};

// --- Meeting Methods ---
const initiateMeeting = async () => {
  if (!currentThread.value) return;
  try {
    const data = await safeFetch('/api/meetings.php?action=create', { method: 'POST' });
    if (data.success) {
      await startMeeting(data);
    }
  } catch (e) {
    globalError.value = "Failed to start meeting: " + e.message;
  }
};

const startMeeting = async (meetingData) => {
  activeMeeting.value = meetingData;
  try {
    localStream.value = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    await nextTick();
    if (localVideoRef.value) {
      localVideoRef.value.srcObject = localStream.value;
    }
    
    // Start Signaling Polling
    startSignalPolling();
    
    // Notify current members (for now we just check signaling activity)
    const membersData = await safeFetch(`/api/meetings.php?action=get_members&room_id=${activeMeeting.value.room_id}`);
    if (membersData.success) {
      membersData.members.forEach(m => {
        initiatePeerConnection(m.sender_id, m.username);
      });
    }
  } catch (e) {
    globalError.value = "Media error: " + e.message;
    leaveMeeting();
  }
};

const startSignalPolling = () => {
  signalPolling.value = setInterval(async () => {
    if (!activeMeeting.value) return;
    try {
      const data = await safeFetch(`/api/meetings.php?action=get_signaling&room_id=${activeMeeting.value.room_id}&last_id=${lastSignalId.value}`);
      if (data.success && data.signals.length > 0) {
        for (const sig of data.signals) {
          lastSignalId.value = Math.max(lastSignalId.value, sig.id);
          await handleSignal(sig);
        }
      }
    } catch (e) { console.error("Signal poll error", e); }
  }, 2000);
};

const handleSignal = async (sig) => {
  const from = sig.sender_id;
  const content = JSON.parse(sig.content);
  if (sig.type === 'offer') {
    const pc = getOrCreatePeer(from, sig.sender_username);
    await pc.setRemoteDescription(new RTCSessionDescription(content));
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    sendSignal(from, 'answer', answer);
  } else if (sig.type === 'answer') {
    const peer = peers.value[from];
    if (peer) await peer.pc.setRemoteDescription(new RTCSessionDescription(content));
  } else if (sig.type === 'candidate') {
    const peer = peers.value[from];
    if (peer) await peer.pc.addIceCandidate(new RTCIceCandidate(content));
  }
};

const initiatePeerConnection = async (targetId, username) => {
  const pc = getOrCreatePeer(targetId, username);
  const offer = await pc.createOffer();
  await pc.setLocalDescription(offer);
  sendSignal(targetId, 'offer', offer);
};

const getOrCreatePeer = (targetId, username) => {
  if (peers.value[targetId]) return peers.value[targetId].pc;
  
  const pc = new RTCPeerConnection(iceServers);
  const activeStream = isScreenSharing.value ? screenStream.value : localStream.value;
  activeStream.getTracks().forEach(track => pc.addTrack(track, activeStream));
  
  pc.onicecandidate = e => {
    if (e.candidate) sendSignal(targetId, 'candidate', e.candidate);
  };
  
  pc.ontrack = e => {
    const stream = e.streams[0];
    peers.value[targetId].stream = stream;
    nextTick(() => {
      const videoEl = remoteVideoRefs.value[targetId];
      if (videoEl) videoEl.srcObject = stream;
    });
  };
  
  pc.onconnectionstatechange = () => {
    if (['disconnected', 'closed', 'failed'].includes(pc.connectionState)) {
      delete peers.value[targetId];
    }
  };
  
  peers.value[targetId] = { pc, username, stream: null };
  return pc;
};

const sendSignal = async (receiverId, type, content) => {
  if (!activeMeeting.value) return;
  await safeFetch(`/api/meetings.php?action=send_signaling`, {
    method: 'POST',
    body: JSON.stringify({
      room_id: activeMeeting.value.room_id,
      receiver_id: receiverId,
      type,
      content: JSON.stringify(content)
    })
  });
};

const leaveMeeting = () => {
  if (signalPolling.value) clearInterval(signalPolling.value);
  if (localStream.value) localStream.value.getTracks().forEach(t => t.stop());
  if (screenStream.value) screenStream.value.getTracks().forEach(t => t.stop());
  Object.values(peers.value).forEach(p => p.pc.close());
  
  activeMeeting.value = null;
  localStream.value = null;
  peers.value = {};
  isScreenSharing.value = false;
};

const toggleMic = () => {
  isMuted.value = !isMuted.value;
  if (localStream.value) {
    localStream.value.getAudioTracks().forEach(t => t.enabled = !isMuted.value);
  }
};

const toggleVideo = () => {
  isVideoOff.value = !isVideoOff.value;
  if (localStream.value) {
    localStream.value.getVideoTracks().forEach(t => t.enabled = !isVideoOff.value);
  }
};

const toggleScreenShare = async () => {
  if (isScreenSharing.value) {
    stopScreenShare();
  } else {
    await startScreenShare();
  }
};

const startScreenShare = async () => {
  try {
    screenStream.value = await navigator.mediaDevices.getDisplayMedia({ video: true });
    isScreenSharing.value = true;
    const screenTrack = screenStream.value.getVideoTracks()[0];
    
    // Replace track for all peers
    Object.values(peers.value).forEach(p => {
      const sender = p.pc.getSenders().find(s => s.track && s.track.kind === 'video');
      if (sender) sender.replaceTrack(screenTrack);
    });
    
    if (localVideoRef.value) localVideoRef.value.srcObject = screenStream.value;
    screenTrack.onended = stopScreenShare;
  } catch (e) { console.error("Screen share failed", e); }
};

const stopScreenShare = () => {
  if (!isScreenSharing.value) return;
  if (screenStream.value) screenStream.value.getTracks().forEach(t => t.stop());
  isScreenSharing.value = false;
  const videoTrack = localStream.value.getVideoTracks()[0];
  Object.values(peers.value).forEach(p => {
    const sender = p.pc.getSenders().find(s => s.track && s.track.kind === 'video');
    if (sender) sender.replaceTrack(videoTrack);
  });
  if (localVideoRef.value) localVideoRef.value.srcObject = localStream.value;
};

const deleteMessage = async (msgId) => {
  if (!confirm('メッセージを削除してよろしいですか？')) return;
  globalError.value = '';
  try {
    const data = await safeFetch('/api/messages.php', {
      method: 'DELETE',
      body: JSON.stringify({ message_id: msgId })
    });
    if (data.success) {
      await loadMessages();
    } else {
      globalError.value = data.error || "Failed to delete message";
    }
  } catch (e) {
    globalError.value = e.message;
  }
};

const sendMessage = async () => {
  if ((!newMessage.value.trim() && !selectedFile.value) || (!currentThread.value && !currentDM.value) || isSending.value) return;
  
  isSending.value = true;
  globalError.value = '';
  
  let attachmentUrl = null;
  
  if (selectedFile.value) {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (!res.ok || !data.success) {
        globalError.value = data.error || 'Failed to upload file';
        isSending.value = false;
        return;
      }
      attachmentUrl = data.url;
    } catch(e) {
      globalError.value = "Upload error: " + e.message;
      isSending.value = false;
      return;
    }
  }

  try {
    const url = currentThread.value ? '/api/messages.php' : '/api/dm.php';
    const payload = currentThread.value
      ? { thread_id: currentThread.value.id, content: newMessage.value, attachment_path: attachmentUrl }
      : { receiver_id: currentDM.value.id, content: newMessage.value, attachment_path: attachmentUrl };

    const data = await safeFetch(url, {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    if (data.success) {
      newMessage.value = '';
      selectedFile.value = null;
      await loadMessages();
      if (!currentThread.value) await loadDMPartners(); // Refresh DM list order
    } else {
      globalError.value = data.error || "Failed to send message";
    }
  } catch (e) {
    globalError.value = e.message;
  } finally {
    isSending.value = false;
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messageListRef.value) {
      messageListRef.value.scrollTop = messageListRef.value.scrollHeight;
    }
  });
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

let pollInterval;

const loadDMPartners = async () => {
  try {
    const data = await safeFetch('/api/dm.php');
    if (data.success) dmPartners.value = data.partners;
  } catch (e) { /* silent */ }
};

const loadFriends = async () => {
  try {
    const data = await safeFetch('/api/friends.php?action=list');
    if (data.success) friendsList.value = data.friends;
  } catch (e) { /* silent */ }
};

const loadPendingRequests = async () => {
  try {
    const data = await safeFetch('/api/friends.php?action=pending');
    if (data.success) pendingRequests.value = data.requests;
  } catch (e) { /* silent */ }
};

const selectDM = async (partner) => {
  currentThread.value = null;
  currentDM.value = partner;
  await loadMessages();
};

const sendFriendRequest = async (userId) => {
  try {
    const data = await safeFetch('/api/friends.php', {
      method: 'POST',
      body: JSON.stringify({ receiver_id: userId })
    });
    if (data.success) {
      alert('Friend request sent!');
    } else {
      alert(data.error || 'Failed to send request');
    }
  } catch (e) { alert(e.message); }
};

const handleFriendRequest = async (requestId, action) => {
  try {
    const data = await safeFetch('/api/friends.php', {
      method: 'PUT',
      body: JSON.stringify({ request_id: requestId, action })
    });
    if (data.success) {
      await loadPendingRequests();
      await loadFriends();
    }
  } catch (e) { alert(e.message); }
};

const notifyTyping = async () => {
  const now = Date.now();
  if (now - lastTypingNotify.value < 3000) return; // Throttle to every 3s
  lastTypingNotify.value = now;
  
  const threadId = currentThread.value ? currentThread.value.id.toString() : (currentDM.value ? `dm_${currentDM.value.id}` : null);
  if (!threadId) return;
  
  try {
    await safeFetch('/api/typing.php', {
      method: 'POST',
      body: JSON.stringify({ thread_id: threadId })
    });
  } catch (e) { /* silent */ }
};

const loadTypingUsers = async () => {
  const threadId = currentThread.value ? currentThread.value.id.toString() : (currentDM.value ? `dm_${currentDM.value.id}` : null);
  if (!threadId) {
    typingUsers.value = [];
    return;
  }
  
  try {
    const data = await safeFetch(`/api/typing.php?thread_id=${threadId}`);
    if (data.success) typingUsers.value = data.typing_users;
  } catch (e) { /* silent */ }
};

onMounted(async () => {
  await loadThreads();
  await loadUsers();
  await loadUnreadCounts();
  await loadDMPartners();
  await loadFriends();
  await loadPendingRequests();
  
  pollInterval = setInterval(() => {
    if (currentThread.value || currentDM.value) {
      loadMessages();
      loadTypingUsers();
    }
    loadThreads();
    loadUsers();
    loadUnreadCounts();
    loadDMPartners();
    loadFriends();
    loadPendingRequests();
  }, 3000); // Polling every 3s
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
.chat-header {
  padding: 1rem 1.5rem;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-main {
  display: flex;
  flex-direction: column;
}
.chat-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f3f4f6;
  margin: 0;
}
.creator-info {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.1rem;
}

.meeting-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: #000;
  z-index: 2000;
  display: flex;
  flex-direction: column;
}
.meeting-header {
  padding: 1rem;
  background: rgba(15, 23, 42, 0.9);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.meeting-info-top h4 { margin: 0; color: #fff; }
.id-pass { font-size: 0.8rem; color: #94a3b8; font-family: monospace; }
.video-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
  padding: 1rem;
  overflow-y: auto;
}
.video-wrapper {
  position: relative;
  background: #1f2937;
  border-radius: 12px;
  overflow: hidden;
  aspect-ratio: 16/9;
}
.video-wrapper video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.video-label {
  position: absolute;
  bottom: 0.5rem;
  left: 0.5rem;
  background: rgba(0,0,0,0.5);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}
.meeting-controls {
  padding: 1.5rem;
  background: rgba(15, 23, 42, 0.9);
  display: flex;
  justify-content: center;
  gap: 1rem;
}
.control-btn {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  border: none;
  background: #374151;
  color: #fff;
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.control-btn:hover { background: #4b5563; }
.btn-muted { background: #ef4444 !important; }
.btn-active { background: #6366f1 !important; }

.message-item {
  position: relative;
}
.message-actions {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
  background: #1f2937;
  padding: 0.2rem;
  border-radius: 4px;
}
.pinned-badge {
  font-size: 0.75rem;
  margin-left: 0.5rem;
  opacity: 0.8;
}
.is-pinned {
  border-left: 2px solid #fbbf24;
  padding-left: 0.5rem;
}
.reactions-list {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.25rem;
}
.reaction-badge {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 0.1rem 0.4rem;
  font-size: 0.75rem;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.reaction-badge:hover {
  background: rgba(255, 255, 255, 0.2);
}
.reaction-count {
  font-weight: bold;
  opacity: 0.8;
}
.message-item:hover .message-actions {
  opacity: 1;
}
.btn-action {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: #ccc;
  cursor: pointer;
  padding: 0.2rem 0.4rem;
  font-size: 0.8rem;
}
.btn-action:hover {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}
.btn-danger:hover {
  background: #ef4444;
  border-color: #ef4444;
}
.edit-message-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.25rem;
}
.edit-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #4b5563;
  border-radius: 4px;
  background: #374151;
  color: white;
}
.file-upload-btn {
  cursor: pointer;
  padding: 0.5rem;
  color: #9ca3af;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: rgba(255,255,255,0.05);
}
.file-upload-btn:hover { color: white; background: rgba(255,255,255,0.1); }
.file-preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: rgba(255,255,255,0.05);
  border-radius: 4px;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
  color: #fbbf24;
}
.attachment-image {
  max-width: 300px;
  max-height: 300px;
  border-radius: 8px;
  margin-top: 0.5rem;
  display: block;
}
.attachment-file {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
  color: #60a5fa;
  text-decoration: none;
}

.edit-actions {
  display: flex;
  gap: 0.5rem;
}
.btn-save {
  color: #10b981;
}

.thread-item {
  position: relative;
  display: flex !important;
  align-items: center;
}
.thread-content-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}
.unread-badge {
  background: #ef4444;
  color: white;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 0.1rem 0.4rem;
  border-radius: 12px;
  margin-left: auto;
  margin-right: 0.5rem;
}
.user-list {
  padding: 1rem;
  flex: 1;
  overflow-y: auto;
  border-top: 1px solid #374151;
}
.user-list-header h3 {
  font-size: 0.8rem;
  color: #9ca3af;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}
.users {
  list-style: none;
  padding: 0; margin: 0;
}
.user-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0;
  color: #d1d5db;
}
.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}
.status-indicator.online { background: #10b981; }
.status-indicator.busy { background: #ef4444; }
.status-indicator.away { background: #f59e0b; }
.status-indicator.offline { background: #6b7280; }
.user-name {
  font-size: 0.9rem;
}
.custom-status {
  font-size: 0.8rem;
  opacity: 0.6;
}

.thread-hover-actions {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}
.thread-item:hover .thread-hover-actions {
  opacity: 1;
}
.btn-icon-small {
  background: transparent;
  border: none;
  color: #ccc;
  cursor: pointer;
  padding: 0 0.2rem;
  font-size: 0.8rem;
}
.btn-icon-small:hover {
  color: white;
}
.edit-thread-form {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
}
.edit-input-small {
  flex: 1;
  padding: 0.2rem;
  border: 1px solid #4b5563;
  border-radius: 4px;
  background: #374151;
  color: white;
  min-width: 0;
}

.global-error-banner {
  background: var(--danger);
  color: white;
  padding: 0.75rem;
  text-align: center;
  font-size: 0.9rem;
  font-weight: 500;
  z-index: 100;
}
.search-bar {
  display: flex;
  padding: 0.5rem 1rem;
  gap: 0.5rem;
  border-bottom: 1px solid #374151;
}
.search-input {
  flex: 1;
  background: #374151;
  border: 1px solid #4b5563;
  color: white;
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  min-width: 0;
}
.search-modal-content {
  max-width: 600px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}
.search-results-body {
  overflow-y: auto;
  flex: 1;
}
.search-result-item {
  background: rgba(255,255,255,0.05);
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: background 0.2s;
}
.search-result-item:hover {
  background: rgba(255,255,255,0.1);
}
.search-result-meta {
  display: flex;
  gap: 0.5rem;
  font-size: 0.8rem;
  margin-bottom: 0.25rem;
  color: #9ca3af;
}
.search-result-author { font-weight: bold; color: #fff; }
.search-result-thread { color: #60a5fa; }
.text-center { text-align: center; }

.user-info {
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.2s;
}
.user-info:hover {
  background: rgba(255, 255, 255, 0.1);
}
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: #1f2937;
  padding: 1.5rem;
  border-radius: 8px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.modal-body .form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.mt-2 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.ml-auto { margin-left: auto; }
.status-indicator-small {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 4px;
}
.status-indicator-small.online { background: #10b981; }
.status-indicator-small.busy { background: #ef4444; }
.status-indicator-small.away { background: #f59e0b; }
.status-indicator-small.offline { background: #6b7280; }
.user-name-request { font-size: 0.9rem; font-weight: 500; }

.typing-indicator-chat {
  font-size: 0.8rem;
  color: #9ca3af;
  margin-bottom: 0.5rem;
  padding: 0 1rem;
  animation: fadeIn 0.3s;
}
.typing-dots span {
  animation: blink 1.4s infinite both;
  font-weight: bold;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes blink {
  0% { opacity: .2; }
  20% { opacity: 1; }
  100% { opacity: .2; }
}

.social-links-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.social-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.social-icon {
  font-size: 0.8rem;
  font-weight: bold;
  color: #9ca3af;
  width: 60px;
}
.clickable { cursor: pointer; }
.clickable:hover { text-decoration: underline; }

.text-danger { color: #ef4444; font-size: 0.8rem; margin-right: 1rem; }
.modal-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; align-items: center; }
</style>
