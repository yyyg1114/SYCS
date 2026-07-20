<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const serverId = computed(() => route.params.id as string)

const server = ref<any>(null)
const channels = ref<any[]>([])
const members = ref<any[]>([])
const roles = ref<any[]>([])
const invites = ref<any[]>([])
const messages = ref<any[]>([])
const loading = ref(true)
const activeChannelId = ref<string | null>(null)
const messageInput = ref('')
const showMemberList = ref(true)
const showSettings = ref(false)
const settingsTab = ref<'general' | 'roles' | 'invites'>('general')

const activeChannel = computed(() => channels.value.find(c => c.id === activeChannelId.value))

async function loadServer() {
  loading.value = true
  try {
    const data = await $fetch(`/api/servers/${serverId.value}`)
    server.value = data.server
    channels.value = data.channels
    members.value = data.members
    roles.value = data.roles
    if (channels.value.length && !activeChannelId.value) {
      activeChannelId.value = channels.value[0].id
    }
    if (activeChannelId.value) {
      await loadMessages()
    }
  } finally {
    loading.value = false
  }
}

async function loadMessages() {
  if (!activeChannelId.value) return
  const data = await $fetch(`/api/servers/${serverId.value}/channels/${activeChannelId.value}/messages`)
  messages.value = data.messages
}

async function sendMessage() {
  if (!messageInput.value.trim() || !activeChannelId.value) return
  await $fetch(`/api/servers/${serverId.value}/channels/${activeChannelId.value}/messages`, {
    method: 'POST',
    body: { content: messageInput.value },
  })
  messageInput.value = ''
  await loadMessages()
}

async function createChannel() {
  const name = prompt('チャンネル名を入力')
  if (!name) return
  await $fetch(`/api/servers/${serverId.value}/channels`, {
    method: 'POST',
    body: { name },
  })
  await loadServer()
}

async function deleteServer() {
  if (!confirm('本当にこのサーバーを削除しますか？')) return
  await $fetch(`/api/servers/${serverId.value}`, { method: 'DELETE' })
  await navigateTo('/servers')
}

const settingsForm = ref({ name: '', description: '', iconUrl: '' })
const newRole = ref({ name: '', color: '#6366f1', permissions: 0 })
const editingRole = ref<any>(null)

function openSettings(tab: 'general' | 'roles' | 'invites') {
  settingsTab.value = tab
  if (server.value) {
    settingsForm.value = {
      name: server.value.name || '',
      description: server.value.description || '',
      iconUrl: server.value.iconUrl || '',
    }
  }
  if (tab === 'invites') loadInvites()
  showSettings.value = true
}

async function loadInvites() {
  try {
    const data = await $fetch(`/api/servers/${serverId.value}/invites`)
    invites.value = data.invites
  } catch {
    invites.value = []
  }
}

async function saveServerSettings() {
  await $fetch(`/api/servers/${serverId.value}`, {
    method: 'PUT',
    body: settingsForm.value,
  })
  showSettings.value = false
  await loadServer()
}

async function addRole() {
  await $fetch(`/api/servers/${serverId.value}/roles`, {
    method: 'POST',
    body: newRole.value,
  })
  newRole.value = { name: '', color: '#6366f1', permissions: 0 }
  await loadServer()
}

async function updateRole(roleId: string) {
  await $fetch(`/api/servers/${serverId.value}/roles/${roleId}`, {
    method: 'PUT',
    body: editingRole.value,
  })
  editingRole.value = null
  await loadServer()
}

async function deleteRole(roleId: string) {
  if (!confirm('このロールを削除しますか？')) return
  await $fetch(`/api/servers/${serverId.value}/roles/${roleId}`, { method: 'DELETE' })
  await loadServer()
}

async function createInvite() {
  await $fetch(`/api/servers/${serverId.value}/invites`, { method: 'POST' })
  await loadInvites()
}

onMounted(loadServer)

function timeAgo(date: string) {
  const diff = Date.now() - new Date(date).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'たった今'
  if (minutes < 60) return `${minutes}分前`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}時間前`
  const days = Math.floor(hours / 24)
  return `${days}日前`
}
</script>

<template>
  <div class="h-full flex bg-[#0b0f19] text-slate-200 overflow-hidden">
    <!-- Channel Sidebar -->
    <aside class="w-60 bg-slate-900 flex flex-col shrink-0 border-r border-slate-800">
      <div class="h-12 px-4 flex items-center justify-between border-b border-slate-800 shrink-0">
        <h2 class="font-bold text-white truncate text-sm">{{ server?.name || 'サーバー' }}</h2>
        <button @click="openSettings('general')" class="text-slate-500 hover:text-white transition">
          <Icon name="lucide:settings" class="w-4 h-4" />
        </button>
      </div>
      <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
        <div class="flex items-center justify-between px-2 py-1">
          <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">テキストチャンネル</span>
          <button @click="createChannel" class="text-slate-500 hover:text-white transition">
            <Icon name="lucide:plus" class="w-3.5 h-3.5" />
          </button>
        </div>
        <button
          v-for="ch in channels"
          :key="ch.id"
          @click="activeChannelId = ch.id; loadMessages()"
          :class="[
            'w-full text-left px-2 py-1.5 rounded-md transition flex items-center gap-1.5 text-sm',
            activeChannelId === ch.id
              ? 'bg-slate-700/50 text-white'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'
          ]"
        >
          <span class="text-slate-500">#</span>
          <span class="truncate">{{ ch.name }}</span>
        </button>
      </div>
      <div class="p-3 border-t border-slate-800 shrink-0">
        <NuxtLink to="/home" class="flex items-center gap-2 text-sm text-slate-500 hover:text-white transition">
          <Icon name="lucide:arrow-left" class="w-4 h-4" />
          タイムラインに戻る
        </NuxtLink>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Channel Header -->
      <div class="h-12 px-4 flex items-center border-b border-slate-800 shrink-0 gap-2">
        <button @click="showMemberList = !showMemberList" class="lg:hidden text-slate-500 hover:text-white mr-1">
          <Icon name="lucide:users" class="w-4 h-4" />
        </button>
        <span class="text-slate-500 font-semibold text-lg">#</span>
        <span class="font-bold text-white text-sm">{{ activeChannel?.name || 'チャンネルを選択' }}</span>
      </div>

      <!-- Messages -->
      <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <div v-if="!activeChannelId" class="flex items-center justify-center h-full text-slate-500">
          チャンネルを選択してください
        </div>
        <template v-else>
          <div v-for="msg in messages" :key="msg.id" class="flex gap-3 group">
            <img
              v-if="msg.user?.avatarUrl"
              :src="msg.user.avatarUrl"
              class="w-9 h-9 rounded-full mt-0.5 object-cover shrink-0"
            />
            <div v-else class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0 mt-0.5">
              {{ msg.user?.displayName?.charAt(0) || '?' }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-white text-sm hover:underline cursor-pointer">{{ msg.user?.displayName || '不明' }}</span>
                <span class="text-[11px] text-slate-600">{{ timeAgo(msg.createdAt) }}</span>
              </div>
              <p class="text-slate-300 text-sm whitespace-pre-wrap break-words">{{ msg.content }}</p>
            </div>
          </div>
          <div v-if="!messages.length" class="text-center text-slate-500 py-8">
            <p class="text-sm">メッセージはまだありません</p>
            <p class="text-xs text-slate-600 mt-1">最初のメッセージを送信しましょう</p>
          </div>
        </template>
      </div>

      <!-- Message Input -->
      <div v-if="activeChannelId" class="px-4 pb-4 shrink-0">
        <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 rounded-lg px-3 py-2">
          <input
            v-model="messageInput"
            @keydown.enter.prevent="sendMessage"
            type="text"
            :placeholder="`#${activeChannel?.name || ''} にメッセージを送信`"
            class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-slate-200 placeholder-slate-500"
          />
          <button
            @click="sendMessage"
            :disabled="!messageInput.trim()"
            class="text-indigo-400 hover:text-indigo-300 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon name="lucide:send" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Member List -->
    <aside
      v-if="showMemberList"
      class="w-60 bg-slate-900/50 flex flex-col shrink-0 border-l border-slate-800 hidden lg:flex"
    >
      <div class="h-12 px-4 flex items-center border-b border-slate-800 shrink-0">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">メンバー — {{ members.length }}</span>
      </div>
      <div class="flex-1 overflow-y-auto p-3 space-y-1">
        <div v-for="member in members" :key="member.id" class="flex items-center gap-2.5 px-2 py-1.5 rounded-md hover:bg-slate-800/50 transition">
          <div class="relative">
            <img
              v-if="member.avatarUrl"
              :src="member.avatarUrl"
              class="w-8 h-8 rounded-full object-cover"
            />
            <div v-else class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold text-xs">
              {{ member.displayName?.charAt(0) || '?' }}
            </div>
            <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-green-500 border-2 border-slate-900" />
          </div>
          <span class="text-sm text-slate-300 truncate">{{ member.displayName || member.username }}</span>
        </div>
      </div>
    </aside>

    <!-- Settings Modal -->
    <Teleport to="body">
      <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showSettings = false">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col">
          <!-- Tabs -->
          <div class="flex border-b border-slate-800 shrink-0">
            <button
              v-for="tab in ([
                { key: 'general' as const, label: '一般' },
                { key: 'roles' as const, label: 'ロール' },
                { key: 'invites' as const, label: '招待' },
              ])"
              :key="tab.key"
              @click="settingsTab = tab.key"
              :class="[
                'flex-1 px-4 py-3 text-sm font-medium transition',
                settingsTab === tab.key
                  ? 'text-indigo-400 border-b-2 border-indigo-500'
                  : 'text-slate-500 hover:text-slate-300'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <!-- General Settings -->
            <template v-if="settingsTab === 'general'">
              <h3 class="text-lg font-bold text-white">サーバー設定</h3>
              <div class="space-y-3">
                <div>
                  <label class="text-xs text-slate-500 font-medium block mb-1">サーバー名</label>
                  <input v-model="settingsForm.name" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div>
                  <label class="text-xs text-slate-500 font-medium block mb-1">説明</label>
                  <textarea v-model="settingsForm.description" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500 resize-none" />
                </div>
                <div>
                  <label class="text-xs text-slate-500 font-medium block mb-1">アイコンURL</label>
                  <input v-model="settingsForm.iconUrl" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
                </div>
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <button @click="showSettings = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition">キャンセル</button>
                <button @click="saveServerSettings" class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition">保存</button>
              </div>
              <div class="border-t border-slate-800 pt-4 mt-4">
                <button @click="deleteServer" class="px-4 py-2 rounded-lg bg-red-600/20 text-red-400 text-sm hover:bg-red-600/30 transition border border-red-600/30">
                  サーバーを削除
                </button>
              </div>
            </template>

            <!-- Roles -->
            <template v-if="settingsTab === 'roles'">
              <h3 class="text-lg font-bold text-white">ロール管理</h3>
              <div class="space-y-2">
                <div v-for="role in roles" :key="role.id" class="flex items-center justify-between bg-slate-800/50 rounded-lg px-3 py-2">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: role.color || '#6366f1' }" />
                    <span class="text-sm text-white">{{ role.name }}</span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="editingRole = { ...role }"
                      class="text-slate-500 hover:text-white transition text-xs"
                    >
                      <Icon name="lucide:pencil" class="w-3.5 h-3.5" />
                    </button>
                    <button @click="deleteRole(role.id)" class="text-slate-500 hover:text-red-400 transition text-xs">
                      <Icon name="lucide:trash-2" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
              <!-- Add Role -->
              <div v-if="!editingRole" class="border-t border-slate-800 pt-3 space-y-2">
                <h4 class="text-sm font-bold text-slate-400">新しいロール</h4>
                <div class="flex gap-2">
                  <input v-model="newRole.name" placeholder="ロール名" class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
                  <input v-model="newRole.color" type="color" class="w-9 h-9 rounded-lg bg-slate-800 border border-slate-700 cursor-pointer" />
                </div>
                <button @click="addRole" :disabled="!newRole.name.trim()" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50">
                  追加
                </button>
              </div>
              <!-- Edit Role -->
              <div v-if="editingRole" class="border-t border-slate-800 pt-3 space-y-2">
                <h4 class="text-sm font-bold text-slate-400">ロールを編集</h4>
                <div class="flex gap-2">
                  <input v-model="editingRole.name" placeholder="ロール名" class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
                  <input v-model="editingRole.color" type="color" class="w-9 h-9 rounded-lg bg-slate-800 border border-slate-700 cursor-pointer" />
                </div>
                <div class="flex gap-2">
                  <button @click="editingRole = null" class="px-4 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-400 hover:text-white transition">キャンセル</button>
                  <button @click="updateRole(editingRole.id)" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition">保存</button>
                </div>
              </div>
            </template>

            <!-- Invites -->
            <template v-if="settingsTab === 'invites'">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">招待</h3>
                <button @click="createInvite" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-xs font-bold text-white hover:bg-indigo-700 transition flex items-center gap-1">
                  <Icon name="lucide:plus" class="w-3.5 h-3.5" />
                  作成
                </button>
              </div>
              <div class="space-y-2">
                <div v-for="invite in invites" :key="invite.id" class="bg-slate-800/50 rounded-lg px-3 py-2 flex items-center justify-between">
                  <div class="text-sm text-slate-300">
                    <code class="bg-slate-900 px-2 py-0.5 rounded text-indigo-400 text-xs">{{ invite.code }}</code>
                    <span class="text-xs text-slate-600 ml-2">使用: {{ invite.usedCount || 0 }}/{{ invite.maxUses || '∞' }}</span>
                  </div>
                </div>
                <p v-if="!invites.length" class="text-sm text-slate-500 text-center py-4">招待はまだありません</p>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 z-40 flex items-center justify-center bg-[#0b0f19]/80">
      <div class="text-slate-500">読み込み中...</div>
    </div>
  </div>
</template>
