<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const channels = ref<any[]>([])
const loading = ref(true)

async function loadChannels() {
  loading.value = true
  try {
    const data = await $fetch('/api/dm/channels')
    channels.value = data.channels
  } finally {
    loading.value = false
  }
}

onMounted(loadChannels)

function otherMembers(ch: any) {
  return ch.members?.filter((m: any) => m.id !== me.value?.user?.id) || []
}

const { data: me } = await useFetch('/api/auth/me', { key: 'dm-me' })
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <h1 class="text-2xl font-bold text-white">DM</h1>

    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <div v-else-if="!channels.length" class="text-center text-slate-500 py-8">
      <p>まだDMチャンネルがありません</p>
    </div>
    <div v-else class="space-y-2">
      <NuxtLink
        v-for="ch in channels"
        :key="ch.id"
        :to="`/dm/${ch.id}`"
        class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-xl hover:bg-slate-800/50 transition"
      >
        <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shrink-0">
          {{ otherMembers(ch)[0]?.displayName?.charAt(0) || '?' }}
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold text-white truncate">{{ otherMembers(ch).map((m: any) => m.displayName).join(', ') || '不明' }}</p>
          <p class="text-xs text-slate-500">DMを開く</p>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>
