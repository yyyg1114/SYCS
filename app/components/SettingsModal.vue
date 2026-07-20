<script setup lang="ts">
const emit = defineEmits<{ close: [] }>()

const { data: userData, refresh: refreshUser } = await useFetch('/api/auth/me', { key: 'settings-user' })
const user = computed(() => userData.value?.user)

const settings = ref(JSON.parse(user.value?.settings || '{}'))
const displayName = ref(user.value?.displayName || '')
const bio = ref(user.value?.bio || '')
const avatarUrl = ref(user.value?.avatarUrl || '')
const saving = ref(false)
const uploadingAvatar = ref(false)
const message = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const activeCategory = ref('profile')

const categories = [
  { key: 'profile', label: 'プロフィール', icon: 'lucide:user' },
  { key: 'posts', label: '投稿設定', icon: 'lucide:edit-3' },
  { key: 'timeline', label: 'タイムライン', icon: 'lucide:layout' },
  { key: 'privacy', label: 'プライバシー', icon: 'lucide:shield' },
  { key: 'notifications', label: '通知', icon: 'lucide:bell' },
]

// Post button layout
const buttonOrder = ref<string[]>(settings.value.postButtonOrder || ['like', 'repost', 'bookmark', 'view'])
const showViewCount = ref(settings.value.showViewCount ?? true)

// Timeline refresh mode
const refreshMode = ref(settings.value.refreshMode || 'auto')

const allButtons = [
  { key: 'like', label: 'いいね', icon: 'lucide:heart' },
  { key: 'repost', label: 'リポスト', icon: 'lucide:repeat-2' },
  { key: 'bookmark', label: 'ブックマーク', icon: 'lucide:bookmark' },
  { key: 'view', label: '閲覧数', icon: 'lucide:eye' },
]

function moveButton(key: string, dir: -1 | 1) {
  const idx = buttonOrder.value.indexOf(key)
  if (idx === -1) return
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= buttonOrder.value.length) return
  const arr = [...buttonOrder.value]
  ;[arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]]
  buttonOrder.value = arr
}

async function uploadAvatar(file: File) {
  uploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    const res = await $fetch<{ url: string }>('/api/upload/avatar', {
      method: 'POST', body: formData,
    })
    avatarUrl.value = res.url
  } catch (e: any) {
    message.value = e.data?.message || 'アバターアップロードに失敗しました'
  } finally {
    uploadingAvatar.value = false
  }
}

function onAvatarSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  uploadAvatar(input.files[0])
  input.value = ''
}

async function save() {
  saving.value = true
  message.value = ''
  try {
    await $fetch('/api/users/profile', {
      method: 'PUT',
      body: { displayName: displayName.value, bio: bio.value, avatarUrl: avatarUrl.value || null },
    })
    await $fetch('/api/users/settings', {
      method: 'PUT',
      body: {
        postButtonOrder: buttonOrder.value,
        showViewCount: showViewCount.value,
        refreshMode: refreshMode.value,
      },
    })
    message.value = '保存しました'
    await refreshUser()
  } catch (e: any) {
    message.value = e.data?.message || '保存に失敗しました'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="emit('close')">
      <div class="absolute inset-0 bg-black/60" />
      <div class="relative bg-[#151a24] border border-slate-700 rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex">
        <!-- Sidebar -->
        <div class="w-44 shrink-0 border-r border-slate-800 p-3 space-y-1 overflow-y-auto">
          <button v-for="cat in categories" :key="cat.key"
            @click="activeCategory = cat.key"
            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition text-left"
            :class="activeCategory === cat.key ? 'bg-indigo-600/20 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/30'">
            <Icon :name="cat.icon" class="w-4 h-4 shrink-0" />
            {{ cat.label }}
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 p-6 overflow-y-auto space-y-5">
          <div class="flex items-center justify-between mb-2">
            <h2 class="text-xl font-bold text-white">
              {{ categories.find(c => c.key === activeCategory)?.label || '設定' }}
            </h2>
            <button @click="emit('close')" class="text-slate-500 hover:text-white transition">
              <Icon name="lucide:x" class="w-5 h-5" />
            </button>
          </div>

          <div v-if="message" class="bg-indigo-500/10 border border-indigo-500/30 rounded-lg p-3 text-sm text-indigo-400">
            {{ message }}
          </div>

          <!-- Profile -->
          <div v-if="activeCategory === 'profile'" class="space-y-4">
            <div class="flex items-center gap-4">
              <button @click="fileInput?.click()" class="relative group shrink-0" :disabled="uploadingAvatar">
                <img v-if="avatarUrl" :src="avatarUrl" class="w-16 h-16 rounded-full object-cover" />
                <div v-else class="w-16 h-16 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                  {{ user?.displayName?.charAt(0) || '?' }}
                </div>
                <div class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                  <Icon v-if="uploadingAvatar" name="lucide:loader-2" class="w-5 h-5 text-white animate-spin" />
                  <Icon v-else name="lucide:camera" class="w-5 h-5 text-white" />
                </div>
              </button>
              <div class="text-sm text-slate-400">
                <p class="font-bold text-white">{{ user?.displayName }}</p>
                <p>@{{ user?.username }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm text-slate-400 mb-1">表示名</label>
              <input v-model="displayName" type="text"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">自己紹介</label>
              <textarea v-model="bio" rows="3"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 resize-none" />
            </div>
            <input ref="fileInput" type="file" accept=".png,.jpeg,.jpg,.gif,.webp" class="hidden" @change="onAvatarSelect" />
          </div>

          <!-- Post Settings -->
          <div v-if="activeCategory === 'posts'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-2">ボタンの表示順</label>
              <p class="text-xs text-slate-600 mb-3">各投稿の下にあるボタンの並び順をカスタマイズできます</p>
              <div class="space-y-2">
                <div v-for="key in buttonOrder" :key="key" class="flex items-center gap-2 bg-slate-800/50 rounded-lg px-3 py-2">
                  <Icon :name="allButtons.find(b => b.key === key)?.icon || ''" class="w-4 h-4 text-slate-400" />
                  <span class="flex-1 text-sm text-white">{{ allButtons.find(b => b.key === key)?.label }}</span>
                  <button @click="moveButton(key, -1)" :disabled="buttonOrder.indexOf(key) === 0"
                    class="p-1 text-slate-500 hover:text-white disabled:opacity-30 transition">
                    <Icon name="lucide:chevron-up" class="w-4 h-4" />
                  </button>
                  <button @click="moveButton(key, 1)" :disabled="buttonOrder.indexOf(key) === buttonOrder.length - 1"
                    class="p-1 text-slate-500 hover:text-white disabled:opacity-30 transition">
                    <Icon name="lucide:chevron-down" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
              <div>
                <p class="text-sm font-medium text-white">閲覧数を表示</p>
                <p class="text-xs text-slate-500">投稿に閲覧数を表示します</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="showViewCount" class="sr-only peer" />
                <div class="w-9 h-5 bg-slate-700 rounded-full peer peer-checked:bg-indigo-600 transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition peer-checked:after:translate-x-4"></div>
              </label>
            </div>
          </div>

          <!-- Timeline Settings -->
          <div v-if="activeCategory === 'timeline'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-2">更新モード</label>
              <p class="text-xs text-slate-600 mb-3">タイムラインの更新方法を選択します</p>
              <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-lg cursor-pointer"
                  :class="refreshMode === 'auto' ? 'ring-1 ring-indigo-500' : ''">
                  <input type="radio" v-model="refreshMode" value="auto" class="sr-only" />
                  <Icon name="lucide:radio" class="w-5 h-5 shrink-0"
                    :class="refreshMode === 'auto' ? 'text-indigo-400' : 'text-slate-600'" />
                  <div>
                    <p class="text-sm font-medium text-white">自動更新</p>
                    <p class="text-xs text-slate-500">新しい投稿が自動でタイムラインに表示されます</p>
                  </div>
                </label>
                <label class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-lg cursor-pointer"
                  :class="refreshMode === 'manual' ? 'ring-1 ring-indigo-500' : ''">
                  <input type="radio" v-model="refreshMode" value="manual" class="sr-only" />
                  <Icon name="lucide:radio" class="w-5 h-5 shrink-0"
                    :class="refreshMode === 'manual' ? 'text-indigo-400' : 'text-slate-600'" />
                  <div>
                    <p class="text-sm font-medium text-white">手動更新</p>
                    <p class="text-xs text-slate-500">更新ボタンを押したときのみタイムラインを更新します</p>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Privacy Settings -->
          <div v-if="activeCategory === 'privacy'" class="space-y-4">
            <div class="p-3 bg-slate-800/30 rounded-lg">
              <p class="text-sm font-medium text-white">アカウントの非公開設定</p>
              <p class="text-xs text-slate-500 mt-1">現在のアカウントは公開設定です（近日対応予定）</p>
            </div>
            <div class="p-3 bg-slate-800/30 rounded-lg">
              <p class="text-sm font-medium text-white">データのエクスポート</p>
              <p class="text-xs text-slate-500 mt-1">アカウントデータのダウンロード（近日対応予定）</p>
            </div>
          </div>

          <!-- Notifications -->
          <div v-if="activeCategory === 'notifications'" class="space-y-4">
            <div class="p-3 bg-slate-800/30 rounded-lg">
              <p class="text-sm font-medium text-white">通知設定</p>
              <p class="text-xs text-slate-500 mt-1">通知機能は近日対応予定です</p>
            </div>
          </div>

          <button @click="save" :disabled="saving"
            class="w-full py-2.5 bg-indigo-600 rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
