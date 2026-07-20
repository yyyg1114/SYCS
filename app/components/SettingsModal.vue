<script setup lang="ts">
const emit = defineEmits<{ close: [] }>()

const { data: userData, refresh: refreshUser } = await useFetch('/api/auth/me', { key: 'settings-user' })
const user = computed(() => userData.value?.user)
const s = computed(() => JSON.parse(user.value?.settings || '{}'))

const displayName = ref(user.value?.displayName || '')
const bio = ref(user.value?.bio || '')
const avatarUrl = ref(user.value?.avatarUrl || '')
const bannerUrl = ref(user.value?.bannerUrl || '')
const birthday = ref(s.value.birthday || '')
const birthplace = ref(s.value.birthplace || '')
const theme = ref(s.value.theme || 'dark')
const language = ref(s.value.language || 'ja')
const github = ref(s.value.github || '')
const twitter = ref(s.value.twitter || '')
const website = ref(s.value.website || '')

const saving = ref(false)
const uploadingAvatar = ref(false)
const uploadingBanner = ref(false)
const message = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const bannerInput = ref<HTMLInputElement | null>(null)

const activeCategory = ref('profile')

const categories = [
  { key: 'profile', label: 'プロフィール', icon: 'lucide:user' },
  { key: 'detail', label: '詳細情報', icon: 'lucide:info' },
  { key: 'links', label: 'リンク', icon: 'lucide:link' },
  { key: 'posts', label: '投稿設定', icon: 'lucide:edit-3' },
  { key: 'timeline', label: 'タイムライン', icon: 'lucide:layout' },
  { key: 'appearance', label: '外観', icon: 'lucide:palette' },
  { key: 'privacy', label: 'プライバシー', icon: 'lucide:shield' },
  { key: 'notifications', label: '通知', icon: 'lucide:bell' },
]

const buttonOrder = ref<string[]>(s.value.postButtonOrder || ['like', 'repost', 'bookmark', 'view'])
const showViewCount = ref(s.value.showViewCount ?? true)
const refreshMode = ref(s.value.refreshMode || 'auto')

const allButtons = [
  { key: 'like', label: 'いいね', icon: 'lucide:heart' },
  { key: 'repost', label: 'リポスト', icon: 'lucide:repeat-2' },
  { key: 'bookmark', label: 'ブックマーク', icon: 'lucide:bookmark' },
  { key: 'view', label: '閲覧数', icon: 'lucide:eye' },
]

function moveButton(key: string, dir: -1 | 1) {
  const idx = buttonOrder.value.indexOf(key); if (idx === -1) return
  const n = idx + dir; if (n < 0 || n >= buttonOrder.value.length) return
  const a = [...buttonOrder.value]; [a[idx], a[n]] = [a[n], a[idx]]; buttonOrder.value = a
}

async function uploadOne(ref: Ref<string>, input: Ref<HTMLInputElement | null>, loadingRef: Ref<boolean>, endpoint: string) {
  const inp = input.value; if (!inp?.files?.length) return
  loadingRef.value = true
  try {
    const fd = new FormData(); fd.append('file', inp.files[0])
    const res = await $fetch<{ url: string }>(endpoint, { method: 'POST', body: fd })
    ref.value = res.url
  } catch (e: any) { message.value = e.data?.message || 'アップロードに失敗しました' }
  finally { loadingRef.value = false; inp.value = '' }
}

function onAvatarSelect() { uploadOne(avatarUrl, fileInput, uploadingAvatar, '/api/upload/avatar') }
function onBannerSelect() { uploadOne(bannerUrl, bannerInput, uploadingBanner, '/api/upload/banner') }

async function save() {
  saving.value = true; message.value = ''
  try {
    await $fetch('/api/users/profile', {
      method: 'PUT', body: { displayName: displayName.value, bio: bio.value, avatarUrl: avatarUrl.value || null, bannerUrl: bannerUrl.value || null },
    })
    await $fetch('/api/users/settings', {
      method: 'PUT', body: {
        postButtonOrder: buttonOrder.value, showViewCount: showViewCount.value, refreshMode: refreshMode.value,
        birthday: birthday.value, birthplace: birthplace.value, theme: theme.value, language: language.value,
        github: github.value, twitter: twitter.value, website: website.value,
      },
    })
    message.value = '保存しました'; await refreshUser(); await refreshNuxtData()
  } catch (e: any) { message.value = e.data?.message || '保存に失敗しました' }
  finally { saving.value = false }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="emit('close')">
      <div class="absolute inset-0 bg-black/60" />
      <div class="relative bg-[#151a24] border border-slate-700 rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex">
        <div class="w-44 shrink-0 border-r border-slate-800 p-3 space-y-1 overflow-y-auto">
          <button v-for="cat in categories" :key="cat.key"
            @click="activeCategory = cat.key"
            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition text-left"
            :class="activeCategory === cat.key ? 'bg-indigo-600/20 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/30'">
            <Icon :name="cat.icon" class="w-4 h-4 shrink-0" /> {{ cat.label }}
          </button>
        </div>
        <div class="flex-1 p-6 overflow-y-auto space-y-5">
          <div class="flex items-center justify-between mb-2">
            <h2 class="text-xl font-bold text-white">{{ categories.find(c => c.key === activeCategory)?.label || '設定' }}</h2>
            <button @click="emit('close')" class="text-slate-500 hover:text-white transition">
              <Icon name="lucide:x" class="w-5 h-5" />
            </button>
          </div>
          <div v-if="message" class="bg-indigo-500/10 border border-indigo-500/30 rounded-lg p-3 text-sm text-indigo-400">{{ message }}</div>

          <!-- Profile -->
          <div v-if="activeCategory === 'profile'" class="space-y-4">
            <!-- Profile preview card -->
            <div class="bg-slate-800/30 border border-slate-800 rounded-xl overflow-hidden">
              <div class="h-16 bg-gradient-to-r from-indigo-900/50 to-purple-900/50" />
              <div class="px-4 pb-3">
                <div class="flex items-end -mt-8 mb-2">
                  <img v-if="avatarUrl" :src="avatarUrl" class="w-14 h-14 rounded-full border-4 border-[#151a24] object-cover" />
                  <div v-else class="w-14 h-14 rounded-full border-4 border-[#151a24] bg-indigo-600 flex items-center justify-center text-lg font-bold text-white">
                    {{ displayName?.charAt(0) || '?' }}
                  </div>
                </div>
                <p class="font-bold text-white">{{ displayName || '表示名' }}</p>
                <p class="text-xs text-slate-500">@{{ user?.username }}</p>
                <p v-if="bio" class="text-xs text-slate-400 mt-1">{{ bio }}</p>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="fileInput?.click()" class="flex-1 py-2 rounded-lg bg-slate-800 text-xs text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-1.5" :disabled="uploadingAvatar">
                <Icon v-if="uploadingAvatar" name="lucide:loader-2" class="w-3.5 h-3.5 animate-spin" />
                <Icon v-else name="lucide:camera" class="w-3.5 h-3.5" />
                アバター
              </button>
              <button @click="bannerInput?.click()" class="flex-1 py-2 rounded-lg bg-slate-800 text-xs text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-1.5" :disabled="uploadingBanner">
                <Icon v-if="uploadingBanner" name="lucide:loader-2" class="w-3.5 h-3.5 animate-spin" />
                <Icon v-else name="lucide:image" class="w-3.5 h-3.5" />
                バナー
              </button>
            </div>

            <div>
              <label class="block text-sm text-slate-400 mb-1">表示名</label>
              <input v-model="displayName" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">自己紹介</label>
              <textarea v-model="bio" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 resize-none" />
            </div>
            <input ref="fileInput" type="file" accept=".png,.jpeg,.jpg,.gif,.webp" class="hidden" @change="onAvatarSelect" />
            <input ref="bannerInput" type="file" accept=".png,.jpeg,.jpg,.gif,.webp" class="hidden" @change="onBannerSelect" />
          </div>

          <!-- Detail info -->
          <div v-if="activeCategory === 'detail'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-1">誕生日</label>
              <input v-model="birthday" type="date" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">出身</label>
              <input v-model="birthplace" type="text" placeholder="例: 東京都" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <!-- Links -->
          <div v-if="activeCategory === 'links'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-1">GitHub</label>
              <div class="flex items-center gap-2">
                <Icon name="lucide:github" class="w-4 h-4 text-slate-500 shrink-0" />
                <input v-model="github" type="text" placeholder="ユーザー名" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Twitter / X</label>
              <div class="flex items-center gap-2">
                <Icon name="lucide:twitter" class="w-4 h-4 text-slate-500 shrink-0" />
                <input v-model="twitter" type="text" placeholder="@ユーザー名" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Webサイト</label>
              <div class="flex items-center gap-2">
                <Icon name="lucide:globe" class="w-4 h-4 text-slate-500 shrink-0" />
                <input v-model="website" type="url" placeholder="https://" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
          </div>

          <!-- Post Settings -->
          <div v-if="activeCategory === 'posts'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-2">ボタンの表示順</label>
              <p class="text-xs text-slate-600 mb-3">各投稿の下にあるボタンの並び順をカスタマイズ</p>
              <div class="space-y-2">
                <div v-for="key in buttonOrder" :key="key" class="flex items-center gap-2 bg-slate-800/50 rounded-lg px-3 py-2">
                  <Icon :name="allButtons.find(b => b.key === key)?.icon || ''" class="w-4 h-4 text-slate-400" />
                  <span class="flex-1 text-sm text-white">{{ allButtons.find(b => b.key === key)?.label }}</span>
                  <button @click="moveButton(key, -1)" :disabled="buttonOrder.indexOf(key) === 0" class="p-1 text-slate-500 hover:text-white disabled:opacity-30 transition"><Icon name="lucide:chevron-up" class="w-4 h-4" /></button>
                  <button @click="moveButton(key, 1)" :disabled="buttonOrder.indexOf(key) === buttonOrder.length - 1" class="p-1 text-slate-500 hover:text-white disabled:opacity-30 transition"><Icon name="lucide:chevron-down" class="w-4 h-4" /></button>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
              <div><p class="text-sm font-medium text-white">閲覧数を表示</p><p class="text-xs text-slate-500">投稿に閲覧数を表示</p></div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="showViewCount" class="sr-only peer" />
                <div class="w-9 h-5 bg-slate-700 rounded-full peer peer-checked:bg-indigo-600 transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition peer-checked:after:translate-x-4"></div>
              </label>
            </div>
          </div>

          <!-- Timeline -->
          <div v-if="activeCategory === 'timeline'" class="space-y-4">
            <label class="block text-sm text-slate-400 mb-2">更新モード</label>
            <div class="space-y-2">
              <label class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-lg cursor-pointer" :class="refreshMode === 'auto' ? 'ring-1 ring-indigo-500' : ''">
                <input type="radio" v-model="refreshMode" value="auto" class="sr-only" />
                <Icon name="lucide:radio" class="w-5 h-5 shrink-0" :class="refreshMode === 'auto' ? 'text-indigo-400' : 'text-slate-600'" />
                <div><p class="text-sm font-medium text-white">自動更新（3秒間隔）</p><p class="text-xs text-slate-500">新しい投稿が自動でタイムラインに表示</p></div>
              </label>
              <label class="flex items-center gap-3 p-3 bg-slate-800/30 rounded-lg cursor-pointer" :class="refreshMode === 'manual' ? 'ring-1 ring-indigo-500' : ''">
                <input type="radio" v-model="refreshMode" value="manual" class="sr-only" />
                <Icon name="lucide:radio" class="w-5 h-5 shrink-0" :class="refreshMode === 'manual' ? 'text-indigo-400' : 'text-slate-600'" />
                <div><p class="text-sm font-medium text-white">手動更新</p><p class="text-xs text-slate-500">更新ボタンを押したときのみ更新</p></div>
              </label>
            </div>
          </div>

          <!-- Appearance -->
          <div v-if="activeCategory === 'appearance'" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-400 mb-2">テーマ</label>
              <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer" :class="theme === 'dark' ? 'bg-indigo-600/20 ring-1 ring-indigo-500' : 'bg-slate-800/30'">
                  <input type="radio" v-model="theme" value="dark" class="sr-only" />
                  <div class="w-6 h-6 rounded-full bg-slate-900 border border-slate-600 shrink-0" />
                  <span class="text-sm text-white">ダーク</span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer" :class="theme === 'light' ? 'bg-indigo-600/20 ring-1 ring-indigo-500' : 'bg-slate-800/30'">
                  <input type="radio" v-model="theme" value="light" class="sr-only" />
                  <div class="w-6 h-6 rounded-full bg-white border border-slate-300 shrink-0" />
                  <span class="text-sm text-white">ライト</span>
                </label>
              </div>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-2">言語</label>
              <select v-model="language" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500">
                <option value="ja">日本語</option>
                <option value="en">English</option>
                <option value="zh">中文</option>
                <option value="ko">한국어</option>
              </select>
            </div>
          </div>

          <!-- Privacy -->
          <div v-if="activeCategory === 'privacy'" class="space-y-4">
            <div class="p-3 bg-slate-800/30 rounded-lg"><p class="text-sm font-medium text-white">アカウント設定</p><p class="text-xs text-slate-500 mt-1">現在は公開設定です</p></div>
          </div>

          <!-- Notifications -->
          <div v-if="activeCategory === 'notifications'" class="space-y-4">
            <div class="p-3 bg-slate-800/30 rounded-lg"><p class="text-sm font-medium text-white">通知設定</p><p class="text-xs text-slate-500 mt-1">近日対応予定</p></div>
          </div>

          <button @click="save" :disabled="saving" class="w-full py-2.5 bg-indigo-600 rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
