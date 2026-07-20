<script setup lang="ts">
definePageMeta({ layout: 'default', middleware: 'auth' })

const posts = ref<any[]>([])
const loading = ref(true)
const postError = ref('')

const timeline = useTimeline()
const { connect, events } = useRealtime()

async function loadPosts() {
  loading.value = true
  try {
    const data = await $fetch('/api/posts', { params: { limit: 50, timeline: timeline.value } })
    posts.value = data.posts
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPosts()
  connect()
})

watch(timeline, () => {
  loadPosts()
})

// Real-time: new post arrives → prepend to feed
watch(events, (evts) => {
  for (const e of evts) {
    if (e.type === 'post:created' && e.post) {
      posts.value.unshift(e.post)
    }
  }
  events.value = []
}, { deep: true })

async function createPost(content: string, attachments?: Array<any>) {
  postError.value = ''
  try {
    await $fetch('/api/posts', { method: 'POST', body: { content, attachments } })
    await loadPosts()
  } catch (e: any) {
    postError.value = e.data?.message || '投稿に失敗しました'
  }
}

async function likePost(postId: string) {
  await $fetch(`/api/posts/${postId}/like`, { method: 'POST' })
  const p = posts.value.find(x => x.id === postId)
  if (p) p.likeCount = (p.likeCount || 0) + 1
}

async function repostPost(postId: string) {
  await $fetch(`/api/posts/${postId}/repost`, { method: 'POST' })
  const p = posts.value.find(x => x.id === postId)
  if (p) p.repostCount = (p.repostCount || 0) + 1
}

async function deletePost(postId: string) {
  await $fetch(`/api/posts/${postId}`, { method: 'DELETE' })
  posts.value = posts.value.filter(p => p.id !== postId)
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
        <PostItem v-for="post in posts" :key="post.id" :post="post" @like="likePost" @repost="repostPost" @delete="deletePost" />
        <p v-if="!posts.length" class="text-center text-slate-500 py-8">まだ投稿がありません</p>
      </div>
    </template>
  </div>
</template>
