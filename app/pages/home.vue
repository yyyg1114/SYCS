<script setup lang="ts">
definePageMeta({ layout: 'default', middleware: 'auth' })

const posts = ref<any[]>([])
const loading = ref(true)
const postError = ref('')

const timeline = useTimeline()
const { connect, events } = useRealtime()
const { data: me } = useFetch('/api/auth/me', { key: 'home-me' })

async function loadPosts() {
  loading.value = true
  try {
    const data = await $fetch('/api/posts', { params: { limit: 50, timeline: timeline.value } })
    posts.value = data.posts
    // Track views
    for (const p of data.posts) {
      $fetch(`/api/posts/${p.id}/view`, { method: 'POST' }).catch(() => {})
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPosts()
  connect()
})

watch(timeline, loadPosts)

watch(events, (evts) => {
  for (const e of evts) {
    if (e.type === 'post:created' && e.post) {
      posts.value.unshift(e.post)
    }
  }
  events.value = []
}, { deep: true })

async function createPost(content: string, attachments?: Array<any>, visibility?: string, visibleTo?: string[]) {
  postError.value = ''
  try {
    await $fetch('/api/posts', { method: 'POST', body: { content, attachments, visibility, visibleTo } })
    await loadPosts()
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
  const p = posts.value.find(x => x.id === postId)
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
  const p = posts.value.find(x => x.id === postId)
  if (!p) return
  try {
    const res = await $fetch<{ bookmarked: boolean }>('/api/bookmarks/toggle', {
      method: 'POST',
      body: { postId },
    })
    p.bookmarked = res.bookmarked
  } catch {}
}

async function deletePost(postId: string) {
  await $fetch(`/api/posts/${postId}`, { method: 'DELETE' })
  posts.value = posts.value.filter(p => p.id === postId)
}

async function reportPost(postId: string) {
  alert('報告しました')
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <div v-if="postError" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-sm text-red-400">
      {{ postError }}
    </div>
    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <template v-else>
      <div class="bg-slate-800/50 rounded-xl p-4">
        <PostComposer @submit="createPost" />
      </div>
      <div class="space-y-3">
        <PostItem
          v-for="post in posts"
          :key="post.id"
          :post="post"
          :current-user-id="me?.user?.id"
          @toggle-like="toggleLike"
          @toggle-repost="toggleRepost"
          @toggle-bookmark="toggleBookmark"
          @delete="deletePost"
          @report="reportPost"
        />
        <p v-if="!posts.length" class="text-center text-slate-500 py-8">まだ投稿がありません</p>
      </div>
    </template>
  </div>
</template>
