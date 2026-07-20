<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const channelId = computed(() => route.params.id as string)
const messages = ref<any[]>([])
const messageInput = ref('')
const loading = ref(true)

async function loadMessages() {
  loading.value = true
  try {
    const data = await $fetch(`/api/dm/channels/${channelId.value}/messages`)
    messages.value = data.messages
  } finally {
    loading.value = false
  }
}

onMounted(loadMessages)

async function sendMessage() {
  if (!messageInput.value.trim()) return
  const data = await $fetch(`/api/dm/channels/${channelId.value}/messages`, {
    method: 'POST',
    body: { content: messageInput.value },
  })
  messages.value.push(data.message)
  messageInput.value = ''
}

const { data: me } = await useFetch('/api/auth/me', { key: 'dm-chat-me' })

function timeAgo(date: string) {
  const diff = Date.now() - new Date(date).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'たった今'
  if (minutes < 60) return `${minutes}分前`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}時間前`
  return `${Math.floor(hours / 24)}日前`
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 h-[calc(100vh-56px-32px)] flex flex-col">
    <NuxtLink to="/dm" class="text-sm text-slate-500 hover:text-white transition mb-4 flex items-center gap-1">
      <Icon name="lucide:arrow-left" class="w-4 h-4" />
      DM一覧に戻る
    </NuxtLink>

    <div class="flex-1 overflow-y-auto space-y-3 mb-4">
      <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
      <div v-else-if="!messages.length" class="text-center text-slate-500 py-8">
        <p>メッセージを送信してみましょう</p>
      </div>
      <div v-for="msg in messages" :key="msg.id" class="flex gap-3">
        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0 mt-0.5">
          {{ msg.sender?.displayName?.charAt(0) || '?' }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-bold text-white text-sm">{{ msg.sender?.displayName || '不明' }}</span>
            <span class="text-xs text-slate-600">{{ timeAgo(msg.createdAt) }}</span>
          </div>
          <p class="text-slate-300 text-sm whitespace-pre-wrap break-words">{{ msg.content }}</p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 shrink-0">
      <input
        v-model="messageInput"
        @keydown.enter.prevent="sendMessage"
        type="text"
        placeholder="メッセージを入力"
        class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-slate-200 placeholder-slate-500"
      />
      <button @click="sendMessage" :disabled="!messageInput.trim()" class="text-indigo-400 hover:text-indigo-300 transition disabled:opacity-50">
        <Icon name="lucide:send" class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
