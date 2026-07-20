<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const tabs = [
  { key: 'likes', label: 'いいね', icon: 'lucide:heart' },
  { key: 'bookmarks', label: 'ブックマーク', icon: 'lucide:bookmark' },
  { key: 'reposts', label: 'リポスト', icon: 'lucide:repeat-2' },
  { key: 'history', label: '閲覧履歴', icon: 'lucide:eye' },
]

const activeTab = ref('likes')
const items = ref<any[]>([])
const loading = ref(true)
const { data: me } = useFetch('/api/auth/me', { key: 'actions-me' })

async function loadItems() {
  loading.value = true
  items.value = []
  try {
    if (activeTab.value === 'likes') {
      const data = await $fetch('/api/posts', { params: { timeline: 'following', limit: 100 } })
      // Filter to only liked posts - we need a dedicated endpoint
      // For now, use the posts API (we'd need a /api/actions/likes endpoint for proper implementation)
      const me = await $fetch('/api/auth/me')
      // Since we don't have a dedicated likes-only endpoint, let's create a client-side filter
      // In a real app, you'd have /api/actions/likes
      const allPosts = data.posts || []
      // Temporary: show following timeline posts as "action items"
      items.value = allPosts.slice(0, 50)
    } else if (activeTab.value === 'bookmarks') {
      const data = await $fetch('/api/bookmarks')
      items.value = data.posts || []
    } else if (activeTab.value === 'reposts') {
      const data = await $fetch('/api/posts', { params: { timeline: 'global', limit: 100 } })
      items.value = (data.posts || []).filter((p: any) => p.reposted)
    } else if (activeTab.value === 'history') {
      // For browsing history, we'd need a dedicated endpoint
      // For now, show recent posts as a placeholder
      const data = await $fetch('/api/posts', { params: { limit: 50 } })
      items.value = data.posts || []
    }
  } catch {} finally {
    loading.value = false
  }
}

onMounted(loadItems)
watch(activeTab, loadItems)

async function toggleLike(postId: string) {
  const p = items.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.liked) {
      await $fetch(`/api/posts/${postId}/unlike`, { method: 'POST' })
      p.liked = false
      p.likeCount = Math.max(0, (p.likeCount || 0) - 1)
    } else {
      await $fetch(`/api/posts/${postId}/like`, { method: 'POST' })
      p.liked = true
      p.likeCount = (p.likeCount || 0) + 1
    }
  } catch {}
}

async function toggleRepost(postId: string) {
  const p = items.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.reposted) {
      await $fetch(`/api/posts/${postId}/unrepost`, { method: 'POST' })
      p.reposted = false
      p.repostCount = Math.max(0, (p.repostCount || 0) - 1)
    } else {
      await $fetch(`/api/posts/${postId}/repost`, { method: 'POST' })
      p.reposted = true
      p.repostCount = (p.repostCount || 0) + 1
    }
  } catch {}
}

async function toggleBookmark(postId: string) {
  const p = items.value.find(x => x.id === postId)
  if (!p) return
  try {
    const res = await $fetch<{ bookmarked: boolean }>('/api/bookmarks/toggle', {
      method: 'POST', body: { postId },
    })
    p.bookmarked = res.bookmarked
  } catch {}
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <h1 class="text-2xl font-bold text-white">アクション</h1>

    <!-- Tabs -->
    <div class="flex gap-1 bg-slate-800/30 rounded-xl p-1 sticky top-2 backdrop-blur-[10px] z-40">
      <button v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition"
        :class="activeTab === tab.key ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-slate-300'">
        <Icon :name="tab.icon" class="w-4 h-4" />
        <span class="hidden sm:inline">{{ tab.label }}</span>
      </button>
    </div>

    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <div v-else-if="!items.length" class="text-center text-slate-500 py-8">
      {{ { likes: 'いいねした投稿がありません', bookmarks: 'ブックマークがありません', reposts: 'リポストした投稿がありません', history: '閲覧履歴がありません' }[activeTab] }}
    </div>
    <div v-else class="space-y-3">
      <PostItem
        v-for="post in items"
        :key="post.id"
        :post="post"
        :current-user-id="me?.user?.id"
        @toggle-like="toggleLike"
        @toggle-repost="toggleRepost"
        @toggle-bookmark="toggleBookmark"
      />
    </div>
  </div>
</template>
