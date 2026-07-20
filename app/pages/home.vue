<script setup lang="ts">
definePageMeta({ layout: 'default', middleware: 'auth' })

const posts = ref<any[]>([])
const loading = ref(true)
const manualRefreshing = ref(false)
const postError = ref('')

const timeline = useTimeline()
const { data: me } = useFetch('/api/auth/me', { key: 'home-me' })
const userSettings = ref(JSON.parse(me.value?.user?.settings || '{}'))
const refreshMode = computed(() => userSettings.value.refreshMode || 'auto')

let pollTimer: ReturnType<typeof setTimeout> | null = null
let lastFetchStr = ref('')

function buildCacheKey(p: any[]) {
  return p.slice(0, 20).map(x => `${x.id}:${x.likeCount}:${x.repostCount}`).join(',')
}

async function loadPosts(isPoll = false) {
  if (!isPoll) loading.value = true
  else manualRefreshing.value = true
  try {
    const data = await $fetch('/api/posts', { params: { limit: 50, timeline: timeline.value } })
    const key = buildCacheKey(data.posts || [])
    if (isPoll && key === lastFetchStr.value) return
    lastFetchStr.value = key
    posts.value = data.posts || []
    for (const p of posts.value) {
      $fetch(`/api/posts/${p.id}/view`, { method: 'POST' }).catch(() => {})
    }
  } finally {
    loading.value = false
    manualRefreshing.value = false
  }
}

function startPolling() {
  stopPolling()
  if (refreshMode.value !== 'auto') return
  pollTimer = setTimeout(async function tick() {
    try { await loadPosts(true) } catch {}
    if (refreshMode.value === 'auto') pollTimer = setTimeout(tick, 3000)
  }, 3000)
}

function stopPolling() {
  if (pollTimer) { clearTimeout(pollTimer); pollTimer = null }
}

onMounted(() => {
  loadPosts().then(startPolling)
})

onUnmounted(stopPolling)

watch(timeline, () => {
  stopPolling()
  loadPosts().then(startPolling)
})

async function createPost(content: string, attachments?: Array<any>, visibility?: string, visibleTo?: string[]) {
  postError.value = ''
  try {
    await $fetch('/api/posts', { method: 'POST', body: { content, attachments, visibility, visibleTo } })
    await loadPosts()
    startPolling()
  } catch (e: any) {
    postError.value = e.data?.message || '投稿に失敗しました'
  }
}

async function toggleLike(postId: string) {
  const p = posts.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.liked) {
      await $fetch(`/api/posts/${postId}/unlike`, { method: 'POST' })
      p.liked = false; p.likeCount = Math.max(0, (p.likeCount || 0) - 1)
    } else {
      await $fetch(`/api/posts/${postId}/like`, { method: 'POST' })
      p.liked = true; p.likeCount = (p.likeCount || 0) + 1
    }
  } catch {}
}

async function toggleRepost(postId: string) {
  const p = posts.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.reposted) {
      await $fetch(`/api/posts/${postId}/unrepost`, { method: 'POST' })
      p.reposted = false; p.repostCount = Math.max(0, (p.repostCount || 0) - 1)
    } else {
      await $fetch(`/api/posts/${postId}/repost`, { method: 'POST' })
      p.reposted = true; p.repostCount = (p.repostCount || 0) + 1
    }
  } catch {}
}

async function toggleBookmark(postId: string) {
  const p = posts.value.find(x => x.id === postId)
  if (!p) return
  try {
    const res = await $fetch<{ bookmarked: boolean }>('/api/bookmarks/toggle', { method: 'POST', body: { postId } })
    p.bookmarked = res.bookmarked
  } catch {}
}

async function deletePost(postId: string) {
  await $fetch(`/api/posts/${postId}`, { method: 'DELETE' })
  posts.value = posts.value.filter(p => p.id === postId)
}

function reportPost(postId: string) { alert('報告しました') }
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <div v-if="postError" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-sm text-red-400">{{ postError }}</div>

    <button v-if="refreshMode === 'manual'" @click="loadPosts()" :disabled="manualRefreshing"
      class="mx-auto flex items-center gap-2 px-6 py-2 rounded-full bg-slate-800 text-sm text-slate-300 hover:bg-slate-700 transition disabled:opacity-50">
      <Icon name="lucide:refresh-ccw" class="w-4 h-4" :class="{ 'animate-spin': manualRefreshing }" />
      更新
    </button>

    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <template v-else>
      <div class="bg-slate-800/50 rounded-xl p-4">
        <PostComposer @submit="createPost" />
      </div>
      <div class="space-y-3">
        <PostItem v-for="post in posts" :key="post.id" :post="post"
          :show-view-count="userSettings.showViewCount ?? true" :current-user-id="me?.user?.id"
          @toggle-like="toggleLike" @toggle-repost="toggleRepost" @toggle-bookmark="toggleBookmark"
          @delete="deletePost" @report="reportPost" />
        <p v-if="!posts.length" class="text-center text-slate-500 py-8">まだ投稿がありません</p>
      </div>
    </template>
  </div>
</template>
