<script setup lang="ts">
const props = defineProps<{
  isServerPage?: boolean
  server?: any
}>()

const route = useRoute()
const isHomePage = computed(() => route.path === '/home')
const isProfilePage = computed(() => route.path.startsWith('/profile/'))
const profileHeader = useState<{displayName: string; username: string; avatarUrl: string | null; bannerUrl: string | null} | null>('profile-header-state', () => null)

const showMoreMenu = ref(false)
const timeline = useTimeline()

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
</script>

<template>
  <div>
    <div class="h-14 flex overflow-hidden">
      <!-- Left section: matches left sidebar width -->
      <div class="hidden min-[681px]:flex items-center px-4 w-48 min-[1024px]:w-60 shrink-0 z-40 shadow-[0px_0px_43px_50px_#0b0f19]">
        <NuxtLink v-if="!isServerPage" to="/" class="text-lg font-extrabold tracking-tighter shrink-0">
          SYCS<span class="text-indigo-500">.</span>
        </NuxtLink>
      </div>

      <!-- Center section: matches main content width -->
      <div class="flex-1 flex items-center gap-4 px-4 min-w-0 justify-center relative overflow-hidden">
        <Transition name="fade">
          <div v-if="isProfilePage && profileHeader" key="overlay" class="absolute inset-0">
            <div v-if="profileHeader?.bannerUrl" class="absolute inset-0 bg-cover bg-center" :style="`background-image: url(${profileHeader.bannerUrl})`"></div>
            <div class="absolute inset-0 backdrop-blur-[10px] bg-[#0b0f19]/40"></div>
          </div>
        </Transition>
        <div class="relative flex items-center gap-4 flex-1 min-w-0 justify-center">
          <!-- SYCS logo on mobile / Profile info on mobile -->
          <Transition name="pop" mode="out-in">
            <NuxtLink v-if="!isServerPage && !(isProfilePage && profileHeader)" key="logo" to="/" class="text-lg font-extrabold tracking-tighter shrink-0 min-[681px]:hidden">
              SYCS<span class="text-indigo-500">.</span>
            </NuxtLink>
            <div v-else-if="isProfilePage && profileHeader" key="profile" class="flex items-center gap-3 shrink-0 mx-auto pr-4">
              <img v-if="profileHeader.avatarUrl" :src="profileHeader.avatarUrl" class="w-8 h-8 rounded-full object-cover shrink-0" />
              <div v-else class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">{{ profileHeader.displayName?.charAt(0) || '?' }}</div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate leading-tight">{{ profileHeader.displayName }}</p>
                <p class="text-[10px] text-slate-500 leading-tight">@{{ profileHeader.username }}</p>
              </div>
            </div>
          </Transition>

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

          <div class="flex items-center gap-2 ml-auto" :class="isProfilePage ? 'hidden' : ''">
          </div>
        </div>
      </div>

      <!-- Right section: matches right sidebar width -->
      <div class="hidden min-[1024px]:block w-[280px] shrink-0 z-40 shadow-[0px_0px_43px_50px_#0b0f19]"></div>
    </div>

    <!-- Server banner (only on server pages) -->
    <div v-if="isServerPage && server?.bannerUrl" class="h-24 overflow-hidden">
      <img :src="server.bannerUrl" class="w-full h-full object-cover" />
    </div>
  </div>
</template>

<style scoped>
.pop-enter-active {
  transition: all 0.25s ease-out;
}
.pop-leave-active {
  transition: all 0.15s ease-in;
}
.pop-enter-from {
  opacity: 0;
  transform: scale(0.85);
}
.pop-leave-to {
  opacity: 0;
  transform: scale(0.85);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>