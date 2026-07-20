<script setup lang="ts">
const emit = defineEmits<{ close: [] }>()

const servers = ref<any[]>([])
const loading = ref(true)
const showCreateForm = ref(false)
const showJoinForm = ref(false)
const createForm = ref({ name: '', description: '' })
const joinCode = ref('')

async function loadServers() {
  loading.value = true
  try {
    const data = await $fetch('/api/servers')
    servers.value = data.servers
  } finally {
    loading.value = false
  }
}

onMounted(loadServers)

async function createServer() {
  const data = await $fetch('/api/servers', {
    method: 'POST',
    body: createForm.value,
  })
  showCreateForm.value = false
  createForm.value = { name: '', description: '' }
  await loadServers()
  navigateTo(`/servers/${data.server.id}`)
  emit('close')
}

async function joinServer() {
  if (!joinCode.value.trim()) return
  const data = await $fetch(`/api/servers/join/${joinCode.value}`, { method: 'POST' })
  showJoinForm.value = false
  joinCode.value = ''
  await loadServers()
  navigateTo(`/servers/${data.id}`)
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="emit('close')">
      <div class="absolute inset-0 bg-black/60" />
      <div class="relative bg-[#151a24] border border-slate-700 rounded-2xl w-full max-w-lg max-h-[80vh] overflow-y-auto p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-bold text-white">サーバー一覧</h2>
          <button @click="emit('close')" class="text-slate-500 hover:text-white transition">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>

        <div class="flex gap-2">
          <button @click="showJoinForm = true" class="flex-1 py-2.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition flex items-center justify-center gap-1.5">
            <Icon name="lucide:log-in" class="w-4 h-4" />
            参加
          </button>
          <button @click="showCreateForm = true" class="flex-1 py-2.5 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition flex items-center justify-center gap-1.5">
            <Icon name="lucide:plus" class="w-4 h-4" />
            作成
          </button>
        </div>

        <div v-if="loading" class="text-center text-slate-500 py-6 text-sm">読み込み中...</div>
        <div v-else-if="!servers.length" class="text-center text-slate-500 py-6 text-sm">参加しているサーバーはありません</div>
        <div v-else class="space-y-2">
          <button
            v-for="server in servers"
            :key="server.id"
            @click="navigateTo(`/servers/${server.id}`); emit('close')"
            class="w-full text-left bg-slate-800/30 border border-slate-800 rounded-xl p-3 hover:bg-slate-800/50 hover:border-slate-700 transition flex items-center gap-3"
          >
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shrink-0">
              {{ server.name?.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-sm text-white truncate">{{ server.name }}</h3>
              <p class="text-xs text-slate-500 truncate">{{ server.description || '説明なし' }}</p>
            </div>
          </button>
        </div>

        <!-- Create form overlay -->
        <div v-if="showCreateForm" class="absolute inset-0 bg-[#151a24] rounded-2xl p-6 flex flex-col space-y-4 z-10" @click.stop>
          <h3 class="text-lg font-bold text-white">サーバーを作成</h3>
          <div class="space-y-3 flex-1">
            <div>
              <label class="text-xs text-slate-500 font-medium block mb-1">サーバー名</label>
              <input v-model="createForm.name" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" placeholder="サーバー名" />
            </div>
            <div>
              <label class="text-xs text-slate-500 font-medium block mb-1">説明 (任意)</label>
              <textarea v-model="createForm.description" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500 resize-none" placeholder="説明" />
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showCreateForm = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition">戻る</button>
            <button @click="createServer" :disabled="!createForm.name.trim()" class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50">作成</button>
          </div>
        </div>

        <!-- Join form overlay -->
        <div v-if="showJoinForm" class="absolute inset-0 bg-[#151a24] rounded-2xl p-6 flex flex-col space-y-4 z-10" @click.stop>
          <h3 class="text-lg font-bold text-white">招待コードで参加</h3>
          <div class="flex-1">
            <label class="text-xs text-slate-500 font-medium block mb-1">招待コード</label>
            <input v-model="joinCode" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" placeholder="コードを入力" />
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showJoinForm = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition">戻る</button>
            <button @click="joinServer" :disabled="!joinCode.trim()" class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50">参加</button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
