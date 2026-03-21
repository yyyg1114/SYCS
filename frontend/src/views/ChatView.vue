<template>
  <div class="chat-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2 class="sycs-logo">SYCS</h2>
        <div class="user-info" v-if="authStore.user">
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
          >
            <span class="hash">#</span> {{ thread.title }}
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
              </div>
              <div class="message-text">{{ msg.content }}</div>
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
const messageListRef = ref(null);

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
.global-error-banner {
  background: var(--danger);
  color: white;
  padding: 0.75rem;
  text-align: center;
  font-size: 0.9rem;
  font-weight: 500;
  z-index: 100;
}
</style>
