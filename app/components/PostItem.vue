<script setup lang="ts">
const props = defineProps<{
  post: {
    id: string
    content: string
    imageUrl?: string
    likeCount?: number
    repostCount?: number
    createdAt: string
    attachments?: Array<any>
    user: {
      id: string
      username: string
      displayName: string
      avatarUrl?: string
    }
  }
}>()

const emit = defineEmits<{
  like: [postId: string]
  repost: [postId: string]
  delete: [postId: string]
}>()

function timeAgo(date: string) {
  const now = Date.now()
  const diff = now - new Date(date).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'たった今'
  if (minutes < 60) return `${minutes}分前`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}時間前`
  const days = Math.floor(hours / 24)
  return `${days}日前`
}
</script>

<template>
  <div class="p-4 bg-slate-800/30 border border-slate-800 rounded-xl hover:bg-slate-800/50 transition">
    <div class="flex gap-3">
      <NuxtLink :to="`/profile/${post.user.id}`" class="shrink-0">
        <img v-if="post.user.avatarUrl" :src="post.user.avatarUrl" class="w-10 h-10 rounded-full object-cover" />
        <div v-else class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
          {{ post.user.displayName.charAt(0) }}
        </div>
      </NuxtLink>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <NuxtLink :to="`/profile/${post.user.id}`" class="font-bold text-white hover:underline truncate">
            {{ post.user.displayName }}
          </NuxtLink>
          <span class="text-slate-500 text-sm shrink-0">@{{ post.user.username }} · {{ timeAgo(post.createdAt) }}</span>
        </div>
        <p class="text-slate-200 leading-relaxed whitespace-pre-wrap break-words">{{ post.content }}</p>
        <img v-if="post.imageUrl && !post.attachments?.length" :src="post.imageUrl" class="mt-2 rounded-lg max-h-80 object-cover" />
        <PostAttachments v-if="post.attachments?.length" :attachments="post.attachments" />
        <div class="flex items-center gap-4 mt-3 text-slate-500">
          <button @click="emit('like', post.id)" class="flex items-center gap-1.5 hover:text-indigo-400 transition text-sm">
            <Icon name="lucide:heart" class="w-4 h-4" />
            <span>{{ post.likeCount || 0 }}</span>
          </button>
          <button @click="emit('repost', post.id)" class="flex items-center gap-1.5 hover:text-green-400 transition text-sm">
            <Icon name="lucide:repeat-2" class="w-4 h-4" />
            <span>{{ post.repostCount || 0 }}</span>
          </button>
          <button @click="emit('delete', post.id)" class="flex items-center gap-1.5 hover:text-red-400 transition text-sm ml-auto">
            <Icon name="lucide:trash-2" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
