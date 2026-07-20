<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const userId = computed(() => route.params.id as string)

const { data: me } = await useFetch('/api/auth/me', { key: 'profile-me' })
const isOwnProfile = computed(() => me.value?.user?.id === userId.value)

const profile = ref<any>(null)
const userPosts = ref<any[]>([])
const loading = ref(true)
const showSettings = ref(false)

async function loadProfile() {
  loading.value = true
  try {
    const [profileData, postsData] = await Promise.all([
      $fetch(`/api/users/${userId.value}/profile`),
      $fetch(`/api/users/${userId.value}/posts`),
    ])
    profile.value = profileData
    userPosts.value = postsData.posts
  } finally {
    loading.value = false
  }
}

onMounted(loadProfile)

async function toggleFollow() {
  if (!profile.value) return
  try {
    await $fetch(`/api/users/${userId.value}/follow`, { method: 'POST' })
    await loadProfile()
  } catch {
    await $fetch(`/api/users/${userId.value}/unfollow`, { method: 'POST' })
    await loadProfile()
  }
}

async function toggleCloseFriend() {
  if (!profile.value) return
  try {
    await $fetch(`/api/users/${userId.value}/close-friends`, { method: 'POST' })
  } catch {
    await $fetch(`/api/users/${userId.value}/close-friends`, { method: 'DELETE' })
  }
}

async function sendFriendRequest() {
  try {
    await $fetch(`/api/users/${userId.value}/friends`, { method: 'POST' })
    alert('フレンドリクエストを送信しました')
  } catch {
    alert('既にリクエスト済みです')
  }
}

async function startDM() {
  try {
    const data = await $fetch('/api/dm/channels', {
      method: 'POST',
      body: { participantId: userId.value },
    })
    await navigateTo(`/dm/${data.channel.id}`)
  } catch {
    alert('DMを作成できませんでした')
  }
}

const editForm = ref({ displayName: '', bio: '', avatarUrl: '' })

function openSettings() {
  if (!profile.value) return
  editForm.value = {
    displayName: profile.value.user.displayName || '',
    bio: profile.value.user.bio || '',
    avatarUrl: profile.value.user.avatarUrl || '',
  }
  showSettings.value = true
}

async function saveProfile() {
  await $fetch('/api/users/profile', {
    method: 'PUT',
    body: editForm.value,
  })
  showSettings.value = false
  await loadProfile()
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
</script>

<template>
  <div class="max-w-2xl mx-auto p-4 space-y-4">
    <div v-if="loading" class="text-center text-slate-500 py-8">読み込み中...</div>
    <template v-else-if="profile">
      <div class="bg-slate-800/30 border border-slate-800 rounded-xl overflow-hidden">
        <div class="h-32 bg-gradient-to-r from-indigo-900/50 to-purple-900/50" />
        <div class="px-5 pb-5">
          <div class="flex items-end -mt-12 mb-3">
            <img
              v-if="profile.user.avatarUrl"
              :src="profile.user.avatarUrl"
              class="w-20 h-20 rounded-full border-4 border-[#0b0f19] object-cover"
            />
            <div v-else class="w-20 h-20 rounded-full border-4 border-[#0b0f19] bg-indigo-600 flex items-center justify-center text-2xl font-bold text-white">
              {{ profile.user.displayName?.charAt(0) || '?' }}
            </div>
          </div>
          <div class="flex items-start justify-between">
            <div>
              <h1 class="text-xl font-bold text-white">{{ profile.user.displayName }}</h1>
              <p class="text-slate-500">@{{ profile.user.username }}</p>
              <p v-if="profile.user.bio" class="mt-2 text-slate-300">{{ profile.user.bio }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
              <template v-if="isOwnProfile">
                <button @click="openSettings" class="px-4 py-1.5 rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800 transition flex items-center gap-1.5">
                  <Icon name="lucide:settings" class="w-4 h-4" />
                  編集
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
            <NuxtLink :to="`/profile/${userId}/followers`" class="hover:underline"><span class="font-bold text-white">{{ profile.stats.followers }}</span> <span class="text-slate-500">フォロワー</span></NuxtLink>
            <NuxtLink :to="`/profile/${userId}/following`" class="hover:underline"><span class="font-bold text-white">{{ profile.stats.following }}</span> <span class="text-slate-500">フォロー中</span></NuxtLink>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-1">投稿</h2>
        <div v-for="post in userPosts" :key="post.id" class="p-4 bg-slate-800/30 border border-slate-800 rounded-xl">
          <div class="flex gap-3">
            <NuxtLink :to="`/profile/${post.user.id}`" class="shrink-0">
              <img v-if="post.user.avatarUrl" :src="post.user.avatarUrl" class="w-10 h-10 rounded-full object-cover" />
              <div v-else class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">{{ post.user.displayName.charAt(0) }}</div>
            </NuxtLink>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <NuxtLink :to="`/profile/${post.user.id}`" class="font-bold text-white hover:underline truncate">{{ post.user.displayName }}</NuxtLink>
                <span class="text-slate-500 text-sm shrink-0">@{{ post.user.username }} · {{ timeAgo(post.createdAt) }}</span>
              </div>
              <p class="text-slate-200 whitespace-pre-wrap break-words">{{ post.content }}</p>
              <img v-if="post.imageUrl" :src="post.imageUrl" class="mt-2 rounded-lg max-h-80 object-cover" />
              <div class="flex items-center gap-4 mt-3 text-slate-500 text-sm">
                <span class="flex items-center gap-1.5"><Icon name="lucide:heart" class="w-4 h-4" /> {{ post.likeCount || 0 }}</span>
                <span class="flex items-center gap-1.5"><Icon name="lucide:repeat-2" class="w-4 h-4" /> {{ post.repostCount || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        <p v-if="!userPosts.length" class="text-center text-slate-500 py-8">まだ投稿がありません</p>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showSettings = false">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md mx-4 space-y-4">
          <h2 class="text-lg font-bold text-white">プロフィールを編集</h2>
          <div class="space-y-3">
            <div>
              <label class="text-xs text-slate-500 font-medium block mb-1">表示名</label>
              <input v-model="editForm.displayName" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="text-xs text-slate-500 font-medium block mb-1">自己紹介</label>
              <textarea v-model="editForm.bio" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500 resize-none" />
            </div>
            <div>
              <label class="text-xs text-slate-500 font-medium block mb-1">アバターURL</label>
              <input v-model="editForm.avatarUrl" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-1 focus:ring-indigo-500" />
            </div>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button @click="showSettings = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition">キャンセル</button>
            <button @click="saveProfile" class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition">保存</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
