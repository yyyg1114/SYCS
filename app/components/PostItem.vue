<script setup lang="ts">
const props = defineProps<{
  post: {
    id: string
    content: string
    imageUrl?: string
    likeCount?: number
    repostCount?: number
    viewCount?: number
    createdAt: string
    visibility?: string
    liked?: boolean
    reposted?: boolean
    bookmarked?: boolean
    attachments?: Array<any>
    user: {
      id: string
      username: string
      displayName: string
      avatarUrl?: string
    }
  }
  showViewCount?: boolean
  currentUserId?: string
}>()

const emit = defineEmits<{
  toggleLike: [postId: string]
  toggleRepost: [postId: string]
  toggleBookmark: [postId: string]
  delete: [postId: string]
  report: [postId: string]
}>()

const showMenu = ref(false)

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

const isMine = computed(() => props.currentUserId === props.post.user.id)
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

          <!-- "..." menu -->
          <div class="relative ml-auto">
            <button @click="showMenu = !showMenu" class="p-1 rounded-full text-slate-500 hover:text-white hover:bg-slate-800 transition">
              <Icon name="lucide:ellipsis" class="w-4 h-4" />
            </button>
            <div v-if="showMenu"
              class="absolute top-full right-0 mt-1 bg-slate-900 border border-slate-800 rounded-xl py-1.5 shadow-xl z-50 min-w-40"
              @click.outside="showMenu = false">
              <div class="px-4 py-1.5 text-xs text-slate-500 border-b border-slate-800">
                閲覧数 {{ post.viewCount || 0 }}
              </div>
              <div class="px-4 py-1.5 text-xs text-slate-500">
                公開範囲: {{ { public: '公開', followers: 'フォロワー', close_friends: '親しい友達', specific: '特定の人' }[post.visibility || 'public'] }}
              </div>
              <hr class="border-slate-800 my-1" />
              <button @click="emit('report', post.id); showMenu = false"
                class="w-full text-left px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800/30 transition">
                報告
              </button>
              <button v-if="isMine" @click="emit('delete', post.id); showMenu = false"
                class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-slate-800/30 transition">
                削除
              </button>
            </div>
          </div>
        </div>

        <p class="text-slate-200 leading-relaxed whitespace-pre-wrap break-words">{{ post.content }}</p>

        <PostAttachments v-if="post.attachments?.length" :attachments="post.attachments" />

        <div class="flex items-center gap-4 mt-3 text-slate-500">
          <button @click="emit('toggleLike', post.id)"
            class="flex items-center gap-1.5 transition text-sm"
            :class="post.liked ? 'text-indigo-400' : 'hover:text-indigo-400'">
            <Icon :name="post.liked ? 'lucide:heart' : 'lucide:heart'" class="w-4 h-4" :class="{ 'fill-current': post.liked }" />
            <span>{{ post.likeCount || 0 }}</span>
          </button>

          <button @click="emit('toggleRepost', post.id)"
            class="flex items-center gap-1.5 transition text-sm"
            :class="post.reposted ? 'text-green-400' : 'hover:text-green-400'">
            <Icon name="lucide:repeat-2" class="w-4 h-4" />
            <span>{{ post.repostCount || 0 }}</span>
          </button>

          <button @click="emit('toggleBookmark', post.id)"
            class="flex items-center gap-1.5 transition text-sm"
            :class="post.bookmarked ? 'text-amber-400' : 'hover:text-amber-400'">
            <Icon :name="post.bookmarked ? 'lucide:bookmark' : 'lucide:bookmark'" class="w-4 h-4" :class="{ 'fill-current': post.bookmarked }" />
          </button>

          <span v-if="showViewCount" class="flex items-center gap-1 text-xs text-slate-600 ml-auto">
            <Icon name="lucide:eye" class="w-3.5 h-3.5" />
            {{ post.viewCount || 0 }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
