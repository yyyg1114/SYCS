<script setup lang="ts">
const props = defineProps<{
  isServerPage?: boolean
  server?: any
}>()

const route = useRoute()
const isHomePage = computed(() => route.path === '/home')

const showSettings = ref(false)
const showMoreMenu = ref(false)
const timeline = useTimeline()

const { data: userData } = useFetch('/api/auth/me', { key: 'header-user' })

const timelineTabs = [
  { key: 'recommended', label: 'オススメ' },
  { key: 'global', label: 'グローバル' },
  { key: 'local', label: 'ローカル' },
]

const extraTimelines = [
  { key: 'trending', label: '急上昇' },
  { key: 'following', label: 'フォロー中' },
  { key: 'global', label: 'すべての投稿' },
]

function selectTab(key: string) {
  timeline.value = key
  showMoreMenu.value = false
}

async function handleLogout() {
  await $fetch('/api/auth/signout', { method: 'POST' })
  await navigateTo('/signin')
}
</script>

<template>
  <div>
    <!-- Main header row -->
    <div class="h-14 flex items-center px-4 gap-4">
      <NuxtLink v-if="!isServerPage" to="/home" class="text-lg font-extrabold tracking-tighter shrink-0">
        SYCS<span class="text-indigo-500">.</span>
      </NuxtLink>

      <!-- Server info on server pages -->
      <template v-if="isServerPage && server">
        <div class="flex items-center gap-3 shrink-0">
          <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
            {{ server.name?.charAt(0) || '?' }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-bold text-white truncate leading-tight">{{ server.name }}</p>
            <p class="text-[10px] text-slate-500 leading-tight">サーバー</p>
          </div>
        </div>
      </template>

      <!-- Timeline tabs (only on home page) -->
      <nav v-if="isHomePage" class="hidden sm:flex items-center gap-1 bg-[#05070d] p-0.5 rounded-full border border-slate-800 ml-auto">
        <button
          v-for="tab in timelineTabs"
          :key="tab.key"
          @click="selectTab(tab.key)"
          class="px-4 py-1 rounded-full text-sm font-medium transition whitespace-nowrap"
          :class="(timeline || 'recommended') === tab.key ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-300'"
        >
          {{ tab.label }}
        </button>
        <div class="relative">
          <button
            @click="showMoreMenu = !showMoreMenu"
            class="px-2 py-1 rounded-full text-sm font-medium transition text-slate-500 hover:text-slate-300"
          >
            <Icon name="lucide:plus" class="w-4 h-4" />
          </button>
          <div
            v-if="showMoreMenu"
            class="absolute top-full right-0 mt-1 bg-slate-900 border border-slate-800 rounded-xl py-1.5 shadow-xl z-50 min-w-40"
            @click.outside="showMoreMenu = false"
          >
            <button
              v-for="item in extraTimelines"
              :key="item.key"
              @click="selectTab(item.key)"
              class="w-full text-left px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800/50 transition"
            >
              {{ item.label }}
            </button>
          </div>
        </div>
      </nav>

      <div class="flex items-center gap-2 ml-auto">
        <NuxtLink to="/dm" class="p-1.5 hover:bg-slate-800 rounded-full transition text-slate-400 hover:text-white" title="DM">
          <Icon name="lucide:message-square" class="w-4 h-4" />
        </NuxtLink>
        <button v-if="userData?.user" @click="showSettings = true" class="p-1.5 hover:bg-slate-800 rounded-full transition text-slate-400 hover:text-white" title="設定">
          <Icon name="lucide:settings" class="w-4 h-4" />
        </button>
        <button v-if="userData?.user" @click="handleLogout" class="p-1.5 hover:bg-slate-800 rounded-full transition text-slate-500 hover:text-red-400" title="ログアウト">
          <Icon name="lucide:log-out" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Server banner (only on server pages) -->
    <div v-if="isServerPage && server?.bannerUrl" class="h-24 overflow-hidden">
      <img :src="server.bannerUrl" class="w-full h-full object-cover" />
    </div>

    <SettingsModal v-if="showSettings" @close="showSettings = false" />
  </div>
</template>
