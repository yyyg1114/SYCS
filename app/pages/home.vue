<script setup lang="ts">
definePageMeta({ layout: 'default', middleware: 'auth' })

const posts = ref([])
const loading = ref(true)
const postError = ref('')

const timeline = useTimeline()

async function loadPosts() {
  loading.value = true
  try {
    const data = await $fetch('/api/posts', { params: { limit: 50, timeline: timeline.value } })
    posts.value = data.posts
  } finally {
    loading.value = false
  }
}

onMounted(loadPosts)
watch(timeline, loadPosts)

async function createPost(content: string, imageUrl?: string) {
  postError.value = ''
  try {
    await $fetch('/api/posts', { method: 'POST', body: { content, imageUrl } })
    await loadPosts()
  } catch (e: any) {
    postError.value = e.data?.message || '投稿に失敗しました'
  }
}

async function likePost(postId: string) {
  await $fetch(`/api/posts/${postId}/like`, { method: 'POST' })
}

async function repostPost(postId: string) {
  await $fetch(`/api/posts/${postId}/repost`, { method: 'POST' })
}

async function deletePost(postId: string) {
  await $fetch(`/api/posts/${postId}`, { method: 'DELETE' })
  await loadPosts()
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
