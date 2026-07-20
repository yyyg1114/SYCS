<script setup lang="ts">
const emit = defineEmits<{ close: [] }>()

const { data: userData, refresh: refreshUser } = await useFetch('/api/auth/me', { key: 'settings-user' })
const user = computed(() => userData.value?.user)

const displayName = ref(user.value?.displayName || '')
const bio = ref(user.value?.bio || '')
const avatarUrl = ref(user.value?.avatarUrl || '')
const saving = ref(false)
const message = ref('')

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

          <div>
            <label class="block text-sm text-slate-400 mb-1">アバター画像URL</label>
            <input v-model="avatarUrl" type="text" placeholder="https://..."
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
          </div>
        </div>

        <button @click="save" :disabled="saving"
          class="w-full py-2.5 bg-indigo-600 rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
          {{ saving ? '保存中...' : '保存' }}
        </button>
      </div>
    </div>
  </Teleport>
</template>
