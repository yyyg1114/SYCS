<template>
  <div class="chat-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2 class="sycs-logo">SYCS</h2>
        <div class="user-info" v-if="authStore.user">
          <div class="user-info-main" @click="openProfileModal" title="Edit Profile">
            <div class="avatar">{{ authStore.user.username.charAt(0).toUpperCase() }}</div>
            <div class="user-details-sidebar">
              <span class="username">{{ authStore.user.username }}</span>
              <span class="status-indicator-small" :class="profileForm.status"></span>
            </div>
          </div>
          <button class="btn-icon-small btn-settings" @click="openProfileModal" title="Settings">⚙️</button>
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

      <!-- Favorites Section -->
      <div v-if="favorites.threads.length > 0 || favorites.dms.length > 0" class="thread-list">
        <div class="thread-list-header">
          <h3>⭐ Favorites</h3>
        </div>
        <ul class="threads">
          <li v-for="thread in favorites.threads" :key="'fav_t_'+thread.id" 
              class="thread-item" :class="{ active: currentThread && currentThread.id === thread.id }"
              @click="selectThread(thread)">
            <div class="thread-content-wrapper">
              <span><span class="hash">#</span> {{ thread.title }}</span>
            </div>
          </li>
          <li v-for="partner in favorites.dms" :key="'fav_dm_'+partner.id" 
              class="thread-item" :class="{ active: currentDM && currentDM.id === partner.id }"
              @click="selectDM(partner)">
            <div class="thread-content-wrapper">
              <span><span class="status-indicator-small" :class="partner.status || 'offline'"></span> {{ partner.username }}</span>
            </div>
          </li>
        </ul>
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
          <div class="header-actions">
            <button @click="toggleFavorite(currentThread ? currentThread.id : currentDM.id, !!currentDM)" 
                    class="btn-icon btn-favorite" 
                    :class="{ 'is-fav': isFavorite(currentThread ? currentThread.id : currentDM.id, !!currentDM) }"
                    title="Toggle Favorite">
              {{ isFavorite(currentThread ? currentThread.id : currentDM.id, !!currentDM) ? '⭐' : '☆' }}
            </button>
            <button v-if="currentThread" @click="initiateMeeting" class="btn-primary btn-sm" title="Start Video Call">
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
            :data-id="msg.id"
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
              
              <!-- Reply Quote -->
              <div v-if="msg.reply_to" class="reply-quote" @click="scrollToMessage(msg.reply_to.id)" @click.stop="handleContentClick">
                <span class="reply-quote-user">@{{ msg.reply_to.username }}</span>
                <span class="reply-quote-content" v-html="formatContent(msg.reply_to.content)"></span>
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
                <div v-else @click="handleContentClick">
                  <div class="msg-content-text" v-html="formatContent(msg.content)"></div>
                  <div v-if="msg.attachment_path" class="message-attachment">
                    <img 
                      v-if="isImage(msg.attachment_path)" 
                      :src="msg.attachment_path" 
                      class="attachment-image clickable-image" 
                      @click="openImageModal(msg.attachment_path)"
                    />
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
              <button v-if="authStore.user" @click="startReply(msg)" class="btn-action" title="Reply">↩️</button>
              <button v-if="authStore.user" @click="togglePin(msg.id)" class="btn-action" :title="msg.is_pinned ? 'Unpin' : 'Pin'">📌</button>
              
              <template v-if="authStore.user && authStore.user.username === msg.username && editingMessageId !== msg.id">
                <button @click="startEdit(msg)" class="btn-action" title="Edit">✎</button>
                <button @click="deleteMessage(msg.id)" class="btn-action btn-danger" title="Delete">🗑</button>
              </template>
            </div>
          </div>
        </div>

        <div class="chat-input-area">
          <div v-if="replyingTo" class="reply-preview">
            <div class="reply-preview-info">
              <span class="reply-preview-label">Replying to <strong>@{{ replyingTo.username }}</strong></span>
              <div class="reply-preview-content">{{ replyingTo.content }}</div>
            </div>
            <button @click="cancelReply" class="btn-icon" title="Cancel Reply">❌</button>
          </div>
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
              @keydown="handleInputKeyDown"
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
        <div class="modal-header-compact">
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

    <!-- Profile Settings Modal -->
    <div v-if="showProfileModal" class="modal-overlay" @click="closeProfileModal">
      <div class="modal-content profile-modal" @click.stop :style="{ borderTop: '8px solid ' + (profileForm.banner_color || '#6366f1') }">
        <div class="modal-header-compact">
          <h3>Profile Settings</h3>
          <button @click="closeProfileModal" class="btn-icon">❌</button>
        </div>
        <div class="modal-body">
          <div class="profile-header-main">
            <div class="profile-avatar-large" :style="{ background: profileForm.banner_color }">
              {{ authStore.user?.username.charAt(0).toUpperCase() }}
            </div>
            <div class="profile-names">
              <h2 class="profile-username">{{ authStore.user?.username }}</h2>
              <div class="form-group-inline">
                <select v-model="profileForm.status" class="edit-input-minimal">
                  <option value="online">Online</option>
                  <option value="busy">Busy</option>
                  <option value="away">Away</option>
                  <option value="offline">Offline</option>
                </select>
              </div>
            </div>
          </div>

          <div class="profile-edit-grid">
            <div class="form-group">
              <label>Custom Status</label>
              <input v-model="profileForm.custom_status" class="edit-input-dark" placeholder="What's on your mind?" />
            </div>
            <div class="form-group">
              <label>Bio</label>
              <textarea v-model="profileForm.bio" class="edit-input-dark" rows="3" placeholder="Tell us about yourself..."></textarea>
            </div>
            
            <div class="form-group">
              <label>Social Links</label>
              <div class="social-input-grid">
                <div class="social-input-item">
                  <span class="social-icon-small">Discord</span>
                  <input v-model="profileForm.social_links.discord" class="edit-input-minimal" placeholder="user#1234" />
                </div>
                <div class="social-input-item">
                  <span class="social-icon-small">GitHub</span>
                  <input v-model="profileForm.social_links.github" class="edit-input-minimal" placeholder="Username" />
                </div>
                <div class="social-input-item">
                  <span class="social-icon-small">Twitter</span>
                  <input v-model="profileForm.social_links.twitter" class="edit-input-minimal" placeholder="@username" />
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Banner Color</label>
              <div class="banner-color-picker">
                <input type="color" v-model="profileForm.banner_color" class="color-dot-input" />
                <span class="color-value-text">{{ profileForm.banner_color }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span v-if="profileSaveError" class="text-danger">{{ profileSaveError }}</span>
          <button class="btn-text" @click="closeProfileModal" style="margin-right: 1rem;">Cancel</button>
          <button class="btn-primary" @click="saveProfile">Save</button>
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
    <!-- Other User Profile Modal -->
    <div v-if="showOtherProfile && selectedUser" class="modal-overlay" @click="showOtherProfile = false">
      <div class="modal-content profile-modal" @click.stop :style="{ borderTop: '8px solid ' + (selectedUser.banner_color || '#6366f1') }">
        <div class="modal-header-compact">
          <h3>User Profile</h3>
          <button @click="showOtherProfile = false" class="btn-icon">❌</button>
        </div>
        <div class="modal-body">
          <div class="profile-header-main">
            <div class="profile-avatar-large">{{ selectedUser.username.charAt(0).toUpperCase() }}</div>
            <div class="profile-names">
              <h2 class="profile-username">{{ selectedUser.username }}</h2>
              <div class="profile-status-box">
                <span class="status-indicator" :class="selectedUser.status"></span>
                <span class="status-text">{{ selectedUser.status }}</span>
              </div>
            </div>
          </div>
          <div v-if="selectedUser.custom_status" class="profile-section">
            <label>Status</label>
            <div class="profile-custom-status-view">💬 {{ selectedUser.custom_status }}</div>
          </div>
          <div class="profile-section">
            <label>Bio</label>
            <div class="profile-bio-view">{{ selectedUser.bio || 'No bio provided.' }}</div>
          </div>
          <div class="profile-section" v-if="selectedUser.social_links && Object.values(selectedUser.social_links).some(v => v)">
            <label>Social Links</label>
            <div class="social-links-grid">
              <div v-if="selectedUser.social_links.discord" class="social-item">
                <span class="social-icon">Discord:</span> {{ selectedUser.social_links.discord }}
              </div>
              <div v-if="selectedUser.social_links.github" class="social-item">
                <span class="social-icon">GitHub:</span> {{ selectedUser.social_links.github }}
              </div>
              <div v-if="selectedUser.social_links.twitter" class="social-item">
                <span class="social-icon">Twitter:</span> {{ selectedUser.social_links.twitter }}
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button v-if="authStore.user && selectedUser.id !== authStore.user.id" class="btn-primary" @click="selectDM(selectedUser); showOtherProfile = false">Send Message</button>
        </div>
      </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div v-if="enlargedImage" class="lightbox-overlay" @click="closeImageModal">
      <div class="lightbox-content" @click.stop>
        <img :src="enlargedImage" class="lightbox-image" />
        <button class="lightbox-close" @click="closeImageModal">✕</button>
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
const replyingTo = ref(null);
const enlargedImage = ref(null);
const favorites = ref({ threads: [], dms: [] });

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
const profileForm = ref({ status: 'online', custom_status: '', bio: '', banner_color: '#6366f1', social_links: {} });
const profileSaveError = ref('');

const showOtherProfile = ref(false);
const selectedUser = ref(null);

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

const startReply = (msg) => {
  replyingTo.value = msg;
};

const cancelReply = () => {
  replyingTo.value = null;
};

const openImageModal = (url) => {
  enlargedImage.value = url;
};

const closeImageModal = () => {
  enlargedImage.value = null;
};

const loadFavorites = async () => {
  try {
    const data = await safeFetch('/api/favorites.php');
    if (data.success) {
      favorites.value = { threads: data.threads, dms: data.dms };
    }
  } catch (e) { /* silent */ }
};

const toggleFavorite = async (id, isDM = false) => {
  try {
    const payload = isDM ? { dm_user_id: id } : { thread_id: id };
    const data = await safeFetch('/api/favorites.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    if (data.success) {
      await loadFavorites();
    }
  } catch (e) { globalError.value = e.message; }
};

const isFavorite = (id, isDM = false) => {
  if (isDM) return favorites.value.dms.some(u => u.id === id);
  return favorites.value.threads.some(t => t.id === id);
};

const formatContent = (content) => {
  if (!content) return '';
  const myName = authStore.user?.username;
  // Escape HTML simply for safety
  let safe = content.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  // Wrap mentions - Pass username in data attribute for click handling
  return safe.replace(/@([^\s!@#$%^&*()=+\[\]{};':"\\|,.<>\/?]+)/gu, (match, username) => {
    const isMe = username === myName ? 'is-me' : '';
    return `<span class="mention ${isMe}" data-username="${username}">${match}</span>`;
  });
};

const handleContentClick = (e) => {
  const mention = e.target.closest('.mention');
  if (mention) {
    const username = mention.dataset.username;
    openOtherProfile(username);
  }
};

const openOtherProfile = (username) => {
  const user = users.value.find(u => u.username === username);
  if (user) {
    selectedUser.value = {
      ...user,
      social_links: user.social_links ? JSON.parse(user.social_links) : {}
    };
    showOtherProfile.value = true;
  }
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
      ? { thread_id: currentThread.value.id, content: newMessage.value, attachment_path: attachmentUrl, reply_to_id: replyingTo.value ? replyingTo.value.id : null }
      : { receiver_id: currentDM.value.id, content: newMessage.value, attachment_path: attachmentUrl, reply_to_id: replyingTo.value ? replyingTo.value.id : null };

    const data = await safeFetch(url, {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    if (data.success) {
      newMessage.value = '';
      selectedFile.value = null;
      replyingTo.value = null;
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

const scrollToMessage = (msgId) => {
  const el = document.querySelector(`.message-item[data-id="${msgId}"]`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.add('highlight-message');
    setTimeout(() => el.classList.remove('highlight-message'), 2000);
  } else {
    alert('メッセージが見つかりません。過去の履歴にある可能性があります。');
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

const handleGlobalKeyDown = (e) => {
  if (e.key === 'Escape') {
    if (enlargedImage.value) closeImageModal();
    else if (showProfileModal.value) closeProfileModal();
    else if (showOtherProfile.value) showOtherProfile.value = false;
    else if (showSearchModal.value) closeSearchModal();
    else if (replyingTo.value) cancelReply();
    else if (editingMessageId.value) cancelEdit();
  }
};

const handleInputKeyDown = (e) => {
  if (e.key === 'ArrowUp' && !newMessage.value && messages.value.length > 0) {
    // Edit last message by current user
    const lastMyMsg = [...messages.value].reverse().find(m => m.username === authStore.user?.username);
    if (lastMyMsg) {
      startEdit(lastMyMsg);
      e.preventDefault();
    }
  }
};

onMounted(async () => {
  await loadThreads();
  await loadUsers();
  await loadUnreadCounts();
  await loadDMPartners();
  await loadFriends();
  await loadPendingRequests();
  await loadFavorites();
  
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
    loadFavorites();
  }, 3000); // Polling every 3s
  
  window.addEventListener('keydown', handleGlobalKeyDown);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
  if (signalPolling.value) clearInterval(signalPolling.value);
  window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>

<style scoped>
.chat-layout {
  display: flex;
  height: 100vh;
  background: #000000; /* Deep black background */
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", sans-serif;
  color: #fff;
  overflow: hidden;
}

.sidebar {
  width: 260px;
  background: rgba(28, 28, 30, 0.7); /* System Gray 6 with transparency */
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  border-right: 0.5px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  z-index: 10;
}

.user-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  margin: 0.5rem;
  transition: background 0.2s;
}
.user-info-main {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  flex: 1;
}
.user-details-sidebar {
  display: flex;
  flex-direction: column;
}
.username {
  font-size: 0.9rem;
  font-weight: 600;
  color: #fff;
}
.btn-settings {
  opacity: 0.6;
  transition: opacity 0.2s, transform 0.2s;
}
.btn-settings:hover {
  opacity: 1;
  transform: rotate(45deg);
}

.chat-header {
  padding: 1rem 1.5rem;
  background: rgba(28, 28, 30, 0.6);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 5;
}

.header-main {
  display: flex;
  flex-direction: column;
}

.chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #000; /* Pure black to let glass layers pop */
  position: relative;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 4px; /* Tight, OS-like spacing */
}

.creator-info {
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.5);
  margin-top: 2px;
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
  transition: background 0.2s cubic-bezier(0.2, 0, 0, 1);
  padding: 0.5rem 1rem;
}
.message-item:hover {
  background: rgba(255, 255, 255, 0.03);
}

.message-actions {
  position: absolute;
  top: 0.25rem;
  right: 1.5rem;
  display: flex;
  gap: 0.35rem;
  opacity: 0;
  transition: opacity 0.15s ease-out;
  background: rgba(44, 44, 46, 0.95); /* System Gray 4 equivalent */
  backdrop-filter: blur(10px);
  padding: 0.3rem;
  border-radius: 10px;
  border: 0.5px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.5);
  z-index: 10;
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
  background: transparent;
  border: none;
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.4rem;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, transform 0.1s;
}
.btn-action:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  transform: scale(1.1);
}
.chat-input-area {
  padding: 1rem 1.5rem 2rem;
  background: transparent;
}
.message-form {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  background: rgba(44, 44, 46, 0.7); /* System Gray 4 */
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 0.6rem 1rem;
  border-radius: 24px; /* Capsule shape */
  border: 0.5px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.chat-input {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 1rem;
  padding: 0.4rem 0;
  outline: none;
  resize: none;
  max-height: 200px;
  line-height: 1.4;
}
.btn-send {
  background: #007aff;
  color: white;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s, background 0.2s;
  flex-shrink: 0;
  margin-bottom: 2px;
}
.btn-send:hover {
  background: #0062cc;
  transform: scale(1.05);
}
.btn-send:disabled {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.3);
  cursor: not-allowed;
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

.sidebar-header {
  padding: 1.5rem 1rem 1rem;
}
.sycs-logo {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #fff, #8e8e93);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.sidebar-section-header {
  padding: 0 1rem;
  margin-bottom: 0.5rem;
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.thread-item {
  margin: 1px 0.5rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
  font-size: 0.9rem;
  font-weight: 500;
}
.thread-item:hover {
  background: rgba(255, 255, 255, 0.05);
}
.thread-item.active {
  background: #007aff; /* Apple System Blue */
  color: #fff;
}
.thread-item.active .hash, .thread-item.active .dm-icon {
  color: rgba(255, 255, 255, 0.8);
}
.hash, .dm-icon {
  color: rgba(255, 255, 255, 0.3);
  margin-right: 4px;
  font-weight: 600;
}
.user-list {
  padding: 1.5rem 1rem;
  flex: 1;
  overflow-y: auto;
  background: rgba(0,0,0,0.2);
}
.user-list-header h3 {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 1rem;
}
.user-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.5rem;
  border-radius: 8px;
  transition: background 0.2s;
}
.user-item:hover {
  background: rgba(255, 255, 255, 0.05);
}
.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
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
  padding: 0.75rem 1rem;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
}
.search-input {
  flex: 1;
  background: rgba(255, 255, 255, 0.05);
  border: 0.5px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: 10px;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  outline: none;
}
.search-input:focus {
  border-color: #007aff;
  background: rgba(255, 255, 255, 0.08);
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
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
}
.modal-content {
  background: rgba(44, 44, 46, 0.95);
  backdrop-filter: blur(25px);
  border-radius: 14px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0,0,0,0.5);
  border: 0.5px solid rgba(255,255,255,0.1);
}
.modal-header-compact {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
.modal-header h3, .modal-header-compact h3 {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.02em;
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

.reply-quote {
  font-size: 0.85rem;
  color: #9ca3af;
  background: rgba(255, 255, 255, 0.05);
  border-left: 2px solid #4f46e5;
  padding: 0.25rem 0.75rem;
  margin-bottom: 0.25rem;
  border-radius: 4px;
  cursor: pointer;
  max-width: fit-content;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.reply-quote:hover { background: rgba(255, 255, 255, 0.1); }
.reply-quote-user { font-weight: bold; color: #818cf8; margin-right: 0.5rem; }

.reply-preview {
  background: #1e1e1e;
  border-left: 4px solid #4f46e5;
  padding: 0.5rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 4px 4px 0 0;
  margin-bottom: -1px;
}
.reply-preview-label { font-size: 0.8rem; color: #9ca3af; }
.reply-preview-content {
  font-size: 0.9rem;
  color: #e5e7eb;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 400px;
}

.highlight-message {
  animation: highlight-pulse 2s ease-out;
}
@keyframes highlight-pulse {
  0% { background-color: rgba(79, 70, 229, 0.3); }
  100% { background-color: transparent; }
}

/* Lightbox Styles */
.clickable-image {
  cursor: zoom-in;
  transition: opacity 0.2s;
}
.clickable-image:hover { opacity: 0.9; }

.lightbox-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.lightbox-image {
  max-width: 95%;
  max-height: 90vh;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
}
.lightbox-close {
  position: absolute;
  top: 2rem;
  right: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  font-size: 1.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  transition: background 0.2s;
}
.lightbox-close:hover { background: rgba(255, 255, 255, 0.2); }
.lightbox-close:hover { color: #818cf8; }

/* Mentions & Favorites Styles */
:deep(.mention) {
  background: rgba(79, 70, 229, 0.15);
  color: #818cf8;
  padding: 0 4px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}
:deep(.mention:hover) { text-decoration: underline; background: rgba(79, 70, 229, 0.3); }
:deep(.mention.is-me) {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

.btn-favorite {
  color: #9ca3af;
  font-size: 1.25rem;
  transition: transform 0.2s, color 0.2s;
}
.btn-favorite:hover { transform: scale(1.2); color: #fbbf24; }
.btn-favorite.is-fav { color: #fbbf24; }

.favorites-list {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 0.5rem;
  margin-bottom: 0.5rem !important;
}

/* Other User Profile Styles */
.profile-modal {
  max-width: 450px;
  width: 90%;
  overflow: hidden;
}
.profile-header-main {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}
.profile-avatar-large {
  width: 80px;
  height: 80px;
  background: #4f46e5;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.profile-names h2 { margin: 0; font-size: 1.5rem; }
.profile-status-box { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; font-size: 0.9rem; color: #9ca3af; }
.profile-section { margin-top: 1.25rem; }
.profile-section label { display: block; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #9ca3af; margin-bottom: 0.5rem; }
.profile-custom-status-view { background: rgba(255,255,255,0.05); padding: 0.75rem; border-radius: 8px; font-style: italic; }
.profile-edit-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.edit-input-dark {
  width: 100%;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 0.75rem;
  color: #fff;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s;
}
.edit-input-dark:focus { border-color: #007aff; }

.edit-input-minimal {
  background: transparent;
  border: none;
  color: #007aff;
  font-weight: 600;
  cursor: pointer;
  outline: none;
}

.social-input-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  padding: 0.75rem;
  border-radius: 10px;
}
.social-input-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.social-icon-small {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.4);
  font-weight: bold;
}

.banner-color-picker {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(0, 0, 0, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 10px;
}
.color-dot-input {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  background: none;
}
.color-dot-input::-webkit-color-swatch-wrapper { padding: 0; }
.color-dot-input::-webkit-color-swatch { border: none; border-radius: 50%; }

.color-value-text {
  font-family: monospace;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.6);
}

.modal-footer {
  margin-top: 2rem;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
}
.modal-footer button {
  min-width: 120px;
  flex: 1;
  max-width: 160px;
  height: 40px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-primary {
  background: #007aff;
  color: #fff;
  border: none;
}
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

.modal-footer .btn-text {
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.7);
  border: 0.5px solid rgba(255, 255, 255, 0.1);
}
.modal-footer .btn-text:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}
</style>
