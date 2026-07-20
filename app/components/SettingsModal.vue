<script setup lang="ts">
const emit = defineEmits<{ close: [] }>()

const { data: userData, refresh: refreshUser } = await useFetch('/api/auth/me', { key: 'settings-user' })
const user = computed(() => userData.value?.user)

const displayName = ref(user.value?.displayName || '')
const bio = ref(user.value?.bio || '')
const avatarUrl = ref(user.value?.avatarUrl || '')
const saving = ref(false)
const uploadingAvatar = ref(false)
const message = ref('')

const fileInput = ref<HTMLInputElement | null>(null)

async function uploadAvatar(file: File) {
  uploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    const res = await $fetch<{ url: string }>('/api/upload/avatar', {
      method: 'POST',
      body: formData,
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
      <div class="relative bg-[#151a24] border border-slate-700 rounded-2xl w-full max-w-lg max-h-[80vh] overflow-y-auto p-6 space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-bold text-white">設定</h2>
          <button @click="emit('close')" class="text-slate-500 hover:text-white transition">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>

        <div v-if="message" class="bg-indigo-500/10 border border-indigo-500/30 rounded-lg p-3 text-sm text-indigo-400">
          {{ message }}
        </div>

        <!-- Avatar -->
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

        <div class="space-y-4">
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
        </div>

        <button @click="save" :disabled="saving"
          class="w-full py-2.5 bg-indigo-600 rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
          {{ saving ? '保存中...' : '保存' }}
        </button>

        <input ref="fileInput" type="file" accept=".png,.jpeg,.jpg,.gif,.webp" class="hidden" @change="onAvatarSelect" />
      </div>
    </div>
  </Teleport>
</template>
