<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const slug = computed(() => route.params.slug as string)

const { data: me } = await useFetch('/api/auth/me', { key: 'profile-me' })
const isOwnProfile = computed(() => {
  if (!me.value?.user) return false
  return me.value.user.username === resolvedUsername.value || me.value.user.id === resolvedId.value
})

const profile = ref<any>(null)
const userPosts = ref<any[]>([])
const loading = ref(true)
const showSettings = ref(false)

const resolvedId = ref('')
const resolvedUsername = ref('')

async function loadProfile() {
  loading.value = true
  try {
    const s = slug.value
    let data
    if (s.startsWith('@')) {
      const username = s.slice(1)
      resolvedUsername.value = username
      data = await $fetch(`/api/users/by-username/${username}`)
      resolvedId.value = data.user.id
    } else {
      data = await $fetch(`/api/users/${s}/profile`)
      resolvedId.value = s
      resolvedUsername.value = data.user.username
    }
    profile.value = data

    const postsData = await $fetch(`/api/users/${resolvedId.value}/posts`)
    userPosts.value = postsData.posts
  } catch {
    profile.value = null
  } finally {
    loading.value = false
  }
}

onMounted(loadProfile)

const settings = computed(() => {
  try { return JSON.parse(profile.value?.user?.settings || '{}') } catch { return {} }
})

async function toggleFollow() {
  if (!profile.value) return
  try {
    await $fetch(`/api/users/${resolvedId.value}/follow`, { method: 'POST' })
    await loadProfile()
  } catch {
    await $fetch(`/api/users/${resolvedId.value}/unfollow`, { method: 'POST' })
    await loadProfile()
  }
}

async function toggleCloseFriend() {
  try {
    await $fetch(`/api/users/${resolvedId.value}/close-friends`, { method: 'POST' })
  } catch {
    await $fetch(`/api/users/${resolvedId.value}/close-friends`, { method: 'DELETE' })
  }
}

async function sendFriendRequest() {
  try {
    await $fetch(`/api/users/${resolvedId.value}/friends`, { method: 'POST' })
    alert('フレンドリクエストを送信しました')
  } catch { alert('既にリクエスト済みです') }
}

async function startDM() {
  try {
    const data = await $fetch('/api/dm/channels', { method: 'POST', body: { participantId: resolvedId.value } })
    await navigateTo(`/dm/${data.channel.id}`)
  } catch { alert('DMを作成できませんでした') }
}

function timeAgo(date: string) {
  const diff = Date.now() - new Date(date).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'たった今'
  if (minutes < 60) return `${minutes}分前`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}時間前`
  return `${Math.floor(hours / 24)}日前`
}

async function toggleLike(postId: string) {
  const p = userPosts.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.liked) { await $fetch(`/api/posts/${postId}/unlike`, { method: 'POST' }); p.liked = false; p.likeCount = Math.max(0, (p.likeCount || 0) - 1) }
    else { await $fetch(`/api/posts/${postId}/like`, { method: 'POST' }); p.liked = true; p.likeCount = (p.likeCount || 0) + 1 }
  } catch {}
}

async function toggleRepost(postId: string) {
  const p = userPosts.value.find(x => x.id === postId)
  if (!p) return
  try {
    if (p.reposted) { await $fetch(`/api/posts/${postId}/unrepost`, { method: 'POST' }); p.reposted = false; p.repostCount = Math.max(0, (p.repostCount || 0) - 1) }
    else { await $fetch(`/api/posts/${postId}/repost`, { method: 'POST' }); p.reposted = true; p.repostCount = (p.repostCount || 0) + 1 }
  } catch {}
}

async function toggleBookmark(postId: string) {
  const p = userPosts.value.find(x => x.id === postId)
  if (!p) return
  try {
    const res = await $fetch<{ bookmarked: boolean }>('/api/bookmarks/toggle', { method: 'POST', body: { postId } })
    p.bookmarked = res.bookmarked
  } catch {}
}

function linkify(text: string) {
  return text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:underline">$1</a>')
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <div v-else-if="!profile" class="text-center text-slate-500 py-8">ユーザーが見つかりません</div>
    <template v-else>
      <div class="bg-slate-800/30 border border-slate-800 rounded-xl overflow-hidden">
        <div class="h-32 bg-gradient-to-r from-indigo-900/50 to-purple-900/50"
          :style="profile.user.bannerUrl ? `background-image: url(${profile.user.bannerUrl}); background-size: cover; background-position: center;` : ''" />
        <div class="px-5 pb-5">
          <div class="flex items-end -mt-12 mb-3">
            <img v-if="profile.user.avatarUrl" :src="profile.user.avatarUrl"
              class="w-20 h-20 rounded-full border-4 border-[#0b0f19] object-cover" />
            <div v-else class="w-20 h-20 rounded-full border-4 border-[#0b0f19] bg-indigo-600 flex items-center justify-center text-2xl font-bold text-white">
              {{ profile.user.displayName?.charAt(0) || '?' }}
            </div>
          </div>
          <div class="flex items-start justify-between">
            <div>
              <h1 class="text-xl font-bold text-white">{{ profile.user.displayName }}</h1>
              <p class="text-slate-500">@{{ profile.user.username }}</p>
              <p v-if="profile.user.bio" class="mt-2 text-slate-300 text-sm">{{ profile.user.bio }}</p>

              <div v-if="settings.website || settings.github || settings.twitter" class="flex flex-wrap gap-3 mt-2">
                <a v-if="settings.website" :href="settings.website" target="_blank" rel="noopener noreferrer"
                  class="flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-400 transition">
                  <Icon name="lucide:globe" class="w-3.5 h-3.5" /> {{ settings.website.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                </a>
                <a v-if="settings.github" :href="`https://github.com/${settings.github}`" target="_blank" rel="noopener noreferrer"
                  class="flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-400 transition">
                  <Icon name="lucide:github" class="w-3.5 h-3.5" /> {{ settings.github }}
                </a>
                <a v-if="settings.twitter" :href="`https://x.com/${settings.twitter.replace('@', '')}`" target="_blank" rel="noopener noreferrer"
                  class="flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-400 transition">
                  <Icon name="lucide:twitter" class="w-3.5 h-3.5" /> {{ settings.twitter }}
                </a>
              </div>
            </div>
            <div class="flex gap-2 shrink-0 flex-wrap justify-end">
              <template v-if="isOwnProfile">
                <button @click="showSettings = true"
                  class="px-4 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition flex items-center gap-1.5">
                  <Icon name="lucide:settings" class="w-4 h-4" /> 設定
                </button>
              </template>
              <template v-else>
                <button @click="toggleFollow" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition">
                  フォロー
                </button>
                <button @click="toggleCloseFriend" class="px-3 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition" title="親しい友達">
                  <Icon name="lucide:heart" class="w-4 h-4" />
                </button>
                <button @click="sendFriendRequest" class="px-3 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition" title="フレンド申請">
                  <Icon name="lucide:user-plus" class="w-4 h-4" />
                </button>
                <button @click="startDM" class="px-3 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition" title="DMを送る">
                  <Icon name="lucide:message-square" class="w-4 h-4" />
                </button>
              </template>
            </div>
          </div>
          <div class="flex gap-5 mt-4 text-sm">
            <div><span class="font-bold text-white">{{ profile.stats.posts }}</span> <span class="text-slate-500">投稿</span></div>
            <span><span class="font-bold text-white">{{ profile.stats.followers }}</span> <span class="text-slate-500">フォロワー</span></span>
            <span><span class="font-bold text-white">{{ profile.stats.following }}</span> <span class="text-slate-500">フォロー中</span></span>
          </div>
          <div v-if="settings.birthday || settings.birthplace" class="flex gap-4 mt-3 text-xs text-slate-500">
            <span v-if="settings.birthday"><Icon name="lucide:cake" class="w-3.5 h-3.5 inline mr-1" />{{ settings.birthday }}</span>
            <span v-if="settings.birthplace"><Icon name="lucide:map-pin" class="w-3.5 h-3.5 inline mr-1" />{{ settings.birthplace }}</span>
          </div>
        </div>
      </div>

      <SettingsModal v-if="isOwnProfile && showSettings" @close="showSettings = false; loadProfile()" />

      <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-1">投稿</h2>
        <div v-for="post in userPosts" :key="post.id" class="p-4 bg-slate-800/30 border border-slate-800 rounded-xl">
          <div class="flex gap-3">
            <NuxtLink :to="`/profile/@${post.user.username}`" class="shrink-0">
              <img v-if="post.user.avatarUrl" :src="post.user.avatarUrl" class="w-10 h-10 rounded-full object-cover" />
              <div v-else class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">{{ post.user.displayName.charAt(0) }}</div>
            </NuxtLink>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <NuxtLink :to="`/profile/@${post.user.username}`" class="font-bold text-white hover:underline truncate">{{ post.user.displayName }}</NuxtLink>
                <span class="text-slate-500 text-sm shrink-0">@{{ post.user.username }} · {{ timeAgo(post.createdAt) }}</span>
              </div>
              <p class="text-slate-200 leading-relaxed whitespace-pre-wrap break-words" v-html="linkify(post.content)" />
              <PostAttachments v-if="post.attachments?.length" :attachments="post.attachments" />
              <div class="flex items-center gap-4 mt-3 text-slate-500">
                <button @click="toggleLike(post.id)" class="flex items-center gap-1.5 transition text-sm" :class="post.liked ? 'text-indigo-400' : 'hover:text-indigo-400'">
                  <svg viewBox="0 0 24 24" class="w-4 h-4" :class="post.liked ? 'fill-indigo-400 stroke-indigo-400' : 'stroke-current fill-none'">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                  </svg>
                  <span>{{ post.likeCount || 0 }}</span>
                </button>
                <button @click="toggleRepost(post.id)" class="flex items-center gap-1.5 transition text-sm" :class="post.reposted ? 'text-green-400' : 'hover:text-green-400'">
                  <Icon name="lucide:repeat-2" class="w-4 h-4" />
                  <span>{{ post.repostCount || 0 }}</span>
                </button>
                <button @click="toggleBookmark(post.id)" class="flex items-center gap-1.5 transition text-sm" :class="post.bookmarked ? 'text-amber-400' : 'hover:text-amber-400'">
                  <svg viewBox="0 0 24 24" class="w-4 h-4" :class="post.bookmarked ? 'fill-amber-400 stroke-amber-400' : 'stroke-current fill-none'">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        <p v-if="!userPosts.length" class="text-center text-slate-500 py-8">まだ投稿がありません</p>
      </div>
    </template>
  </div>
</template>
