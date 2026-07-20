<script setup lang="ts">
const route = useRoute()

const showServerList = ref(false)

const { data: serversData } = useFetch('/api/servers', { key: 'sidebar-servers' })
const servers = computed(() => serversData.value?.servers || [])

const { data: userData } = useFetch('/api/auth/me', { key: 'sidebar-user' })
</script>

<template>
  <aside class="flex flex-col border-r border-slate-800 bg-[#0b0f19] h-screen sticky top-0 px-3 py-4 overflow-y-auto">
    <NuxtLink to="/home" class="px-2 py-2 rounded-lg flex items-center gap-3 transition"
      :class="route.path === '/home' ? 'bg-slate-800/50 text-white font-medium' : 'text-slate-400 hover:bg-slate-800/30'">
      <Icon name="lucide:home" class="w-5 h-5 shrink-0" />
      <span class="text-sm truncate">ホーム</span>
    </NuxtLink>
    <NuxtLink to="/dm" class="px-2 py-2 rounded-lg flex items-center gap-3 transition text-slate-400 hover:bg-slate-800/30 hover:text-slate-100"
      :class="route.path.startsWith('/dm') ? 'bg-slate-800/50 text-white font-medium' : ''">
      <Icon name="lucide:message-square" class="w-5 h-5 shrink-0" />
      <span class="text-sm truncate">DM</span>
    </NuxtLink>
    <NuxtLink to="/notifications" class="px-2 py-2 rounded-lg flex items-center gap-3 transition text-slate-400 hover:bg-slate-800/30 hover:text-slate-100">
      <Icon name="lucide:bell" class="w-5 h-5 shrink-0" />
      <span class="text-sm truncate">通知</span>
    </NuxtLink>
    <NuxtLink to="/actions" class="px-2 py-2 rounded-lg flex items-center gap-3 transition text-slate-400 hover:bg-slate-800/30 hover:text-slate-100">
      <Icon name="lucide:zap" class="w-5 h-5 shrink-0" />
      <span class="text-sm truncate">アクション</span>
    </NuxtLink>

    <div v-if="servers.length" class="mt-6">
      <div class="px-2 mb-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">サーバー</div>
      <div class="space-y-0.5">
        <NuxtLink v-for="s in servers" :key="s.id" :to="`/servers/${s.id}`" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-md transition text-sm"
          :class="route.path === `/servers/${s.id}` ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50'">
          <div class="w-6 h-6 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
            {{ s.name.charAt(0) }}
          </div>
          <span class="truncate">{{ s.name }}</span>
        </NuxtLink>
      </div>
    </div>

    <button @click="showServerList = true" class="mt-4 w-full px-2 py-2 text-slate-500 hover:text-slate-300 hover:bg-slate-800/30 rounded-lg flex items-center gap-3 text-sm transition">
      <Icon name="lucide:plus" class="w-4 h-4" />
      サーバーを探す
    </button>

    <ServerListModal v-if="showServerList" @close="showServerList = false" />

    <div v-if="userData?.user" class="mt-auto pt-4 border-t border-slate-800 flex items-center gap-2 px-2 py-2">
      <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
        {{ userData.user.displayName?.charAt(0) || '?' }}
      </div>
      <NuxtLink :to="`/profile/${userData.user.id}`" class="text-sm text-slate-400 hover:text-white truncate">
        @{{ userData.user.username }}
      </NuxtLink>
    </div>
  </aside>
</template>
