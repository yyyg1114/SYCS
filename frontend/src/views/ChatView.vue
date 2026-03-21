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
              <div class="thread-hover-actions" v-if="authStore.user && thread.creator_name === authStore.user.username">
                <button @click.stop="startThreadEdit(thread)" class="btn-icon-small" title="Edit">✎</button>
                <button @click.stop="deleteThread(thread.id)" class="btn-icon-small btn-danger" title="Delete">🗑</button>
              </div>
            </div>
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

      <template v-if="currentThread">
        <div class="chat-header">
          <h2><span class="hash">#</span> {{ currentThread.title }}</h2>
          <span class="creator-info">Created by {{ currentThread.creator_name }}</span>
        </div>

        <div class="message-list" ref="messageListRef">
          <div v-if="messages.length === 0" class="empty-state">
            Start the conversation in #{{ currentThread.title }}
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
                <div v-else>{{ msg.content }}</div>
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
          <form @submit.prevent="sendMessage" class="message-form">
            <input 
              type="text" 
              v-model="newMessage" 
              placeholder="Message #..." 
              autocomplete="off"
            />
            <button type="submit" :disabled="!newMessage.trim() || isSending" class="btn-send">
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
              <option value="online">🟢 Online</option>
              <option value="busy">🔴 Busy</option>
              <option value="away">🟡 Away</option>
              <option value="offline">⚪ Offline</option>
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
        </div>
        <div class="modal-footer">
          <span v-if="profileSaveError" class="text-danger">{{ profileSaveError }}</span>
          <button class="btn-primary" @click="saveProfile">Save Changes</button>
        </div>
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
const currentThread = ref(null);
const messages = ref([]);
const newMessage = ref('');

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
        banner_color: data.profile.banner_color || '#6366f1'
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
  try {
    const res = await fetch(url, options);
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error(`サーバーエラー: 不正な応答 (${res.status})`);
    }
    return data;
  } catch (err) {
    if (err.name === 'TypeError') {
      throw new Error("通信エラー: PHPサーバーが稼働しているか確認してください。");
    }
    throw err;
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
      selectThread(data.thread);
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

const selectThread = async (thread) => {
  currentThread.value = thread;
  await loadMessages();
};

const loadMessages = async () => {
  if (!currentThread.value) return;
  try {
    const data = await safeFetch(`/api/messages.php?thread_id=${currentThread.value.id}`);
    if (data.success) {
      messages.value = data.messages;
      globalError.value = '';
      scrollToBottom();
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
  if (!newMessage.value.trim() || !currentThread.value || isSending.value) return;
  
  isSending.value = true;
  globalError.value = '';
  try {
    const data = await safeFetch('/api/messages.php', {
      method: 'POST',
      body: JSON.stringify({ 
        thread_id: currentThread.value.id, 
        content: newMessage.value 
      })
    });
    if (data.success) {
      newMessage.value = '';
      await loadMessages();
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
onMounted(async () => {
  await loadThreads();
  pollInterval = setInterval(() => {
    if (currentThread.value) loadMessages();
    loadThreads();
  }, 3000); // Polling every 3s
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
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
.text-danger { color: #ef4444; font-size: 0.8rem; margin-right: 1rem; }
.modal-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; align-items: center; }
</style>
