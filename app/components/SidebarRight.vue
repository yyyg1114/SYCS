<script setup lang="ts">
const { data: userData } = useFetch('/api/auth/me', { key: 'sidebar-right-user' })

const route = useRoute()
const isServerPage = computed(() => route.path.startsWith('/servers/') && route.params.id)

const { data: serverData } = useFetch(
  () => isServerPage.value ? `/api/servers/${route.params.id}` : null,
  { key: 'sidebar-right-server' }
)

const members = computed(() => serverData.value?.members || [])
</script>

<template>
  <aside class="p-4 overflow-y-auto h-[calc(100vh-57px)] sticky top-14">
    <template v-if="isServerPage && members.length">
      <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">メンバー ({{ members.length }})</h3>
      <div class="space-y-2">
        <NuxtLink v-for="m in members" :key="m.userId" :to="`/profile/@${m.user?.username || m.userId}`"
          class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition">
          <div class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold shrink-0">
            {{ m.user?.displayName?.charAt(0) || '?' }}
          </div>
          <span class="truncate">{{ m.user?.displayName || m.nickname || '不明' }}</span>
        </NuxtLink>
      </div>
    </template>

    <template v-else-if="userData?.user">
      <NuxtLink :to="`/profile/@${userData.user.username}`" class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-xl hover:bg-slate-800/50 transition">
        <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shrink-0">
          {{ userData.user.displayName?.charAt(0) || '?' }}
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold text-white truncate">{{ userData.user.displayName }}</p>
          <p class="text-xs text-slate-500 truncate">@{{ userData.user.username }}</p>
        </div>
      </NuxtLink>
    </template>
  </aside>
</template>
