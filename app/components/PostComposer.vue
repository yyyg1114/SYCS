<script setup lang="ts">
const emit = defineEmits<{
  submit: [content: string, attachments?: Array<any>, visibility?: string, visibleTo?: string[]]
}>()

const content = ref('')
const pendingFiles = ref<Array<{
  file: File; preview: string; url?: string; blurUrl?: string | null
  type: string; mime: string; blur: boolean; watermark: boolean
}>>([])
const uploading = ref(false)
const textareaRef = ref<HTMLTextAreaElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const activePreview = ref<number | null>(null)
const showPrivacy = ref(false)

const visibility = ref('public')
const visibleTo = ref<string[]>([])

type VisibilityOption = { key: string; label: string; icon: string }
const visibilityOptions: VisibilityOption[] = [
  { key: 'public', label: 'すべての人に公開', icon: 'lucide:globe' },
  { key: 'followers', label: 'フォロワーのみ', icon: 'lucide:users' },
  { key: 'close_friends', label: '親しい友達のみ', icon: 'lucide:heart' },
  { key: 'specific', label: '特定の人', icon: 'lucide:user-check' },
]

const selectedVis = computed(() => visibilityOptions.find(o => o.key === visibility.value))

const MAX_FILES = 8
const ALLOWED = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'video/webm', 'video/mp4', 'audio/mpeg', 'audio/ogg']

function autoResize() {
  const el = textareaRef.value; if (!el) return
  el.style.height = 'auto'
  el.style.height = `${Math.min(el.scrollHeight, 20 * 8)}px`
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  const remaining = MAX_FILES - pendingFiles.value.length
  for (const f of Array.from(input.files).slice(0, remaining)) {
    if (!ALLOWED.includes(f.type)) continue
    pendingFiles.value.push({
      file: f,
      preview: URL.createObjectURL(f),
      type: f.type.startsWith('image/') ? 'image' : f.type.startsWith('video/') ? 'video' : 'audio',
      mime: f.type,
      blur: false,
      watermark: false,
    })
  }
  input.value = ''
}

function removeFile(index: number) {
  const f = pendingFiles.value[index]
  if (f.preview) URL.revokeObjectURL(f.preview)
  pendingFiles.value.splice(index, 1)
  if (activePreview.value === index) activePreview.value = null
}

async function handleSubmit() {
  if (!content.value.trim() && !pendingFiles.value.length) return
  uploading.value = true
  try {
    let attachments: Array<any> | undefined
    if (pendingFiles.value.length) {
      const formData = new FormData()
      for (const f of pendingFiles.value) formData.append('files', f.file)
      const res = await $fetch<{ files: Array<{ url: string; blurUrl: string | null; type: string; mime: string }> }>('/api/upload', {
        method: 'POST', body: formData,
      })
      attachments = res.files.map((f, i) => ({
        ...f, blur: pendingFiles.value[i]?.blur || false,
        watermark: pendingFiles.value[i]?.watermark || false,
      }))
    }
    emit('submit', content.value, attachments, visibility.value, visibleTo.value.length ? visibleTo.value : undefined)
    content.value = ''
    for (const f of pendingFiles.value) { if (f.preview) URL.revokeObjectURL(f.preview) }
    pendingFiles.value = []
    visibility.value = 'public'
    visibleTo.value = []
    nextTick(autoResize)
  } finally { uploading.value = false }
}

function activeFile() {
  return activePreview.value !== null ? pendingFiles.value[activePreview.value] : null
}

function fileIcon(mime: string) {
  if (mime.startsWith('image/')) return 'lucide:image'
  if (mime.startsWith('video/')) return 'lucide:video'
  if (mime.startsWith('audio/')) return 'lucide:music'
  return 'lucide:file'
}
</script>

<template>
  <div class="flex gap-3">
    <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shrink-0">
      <Icon name="lucide:user" class="w-5 h-5" />
    </div>
    <div class="flex-1 space-y-2">
      <textarea ref="textareaRef" v-model="content" @input="autoResize"
        class="w-full bg-transparent border-none focus:ring-0 text-white placeholder-slate-500 resize-none text-sm leading-5"
        placeholder="なにかあった？" rows="2" />

      <!-- File previews (clickable) -->
      <div v-if="pendingFiles.length" class="flex flex-wrap gap-2">
        <button v-for="(f, i) in pendingFiles" :key="i"
          @click="activePreview = i"
          class="relative w-16 h-16 rounded-lg overflow-hidden bg-slate-900/70 border border-slate-700 hover:border-indigo-500 transition shrink-0">
          <img v-if="f.type === 'image'" :src="f.preview" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-slate-500">
            <Icon :name="fileIcon(f.mime)" class="w-5 h-5" />
          </div>
          <button @click.stop="removeFile(i)" class="absolute top-0.5 right-0.5 p-0.5 rounded-full bg-black/60 text-white hover:bg-black/80">
            <Icon name="lucide:x" class="w-2.5 h-2.5" />
          </button>
        </button>
      </div>

      <!-- Attachment preview modal -->
      <Teleport to="body">
        <div v-if="activePreview !== null && activeFile()" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/70"
          @click.self="activePreview = null">
          <div class="bg-[#151a24] border border-slate-700 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="relative bg-black rounded-t-2xl min-h-[200px] flex items-center justify-center">
              <img v-if="activeFile()!.type === 'image'" :src="activeFile()!.preview" class="max-w-full max-h-[50vh] object-contain rounded-t-2xl" />
              <video v-else-if="activeFile()!.type === 'video'" :src="activeFile()!.preview" controls autoplay muted loop
                class="max-w-full max-h-[50vh] rounded-t-2xl" />
              <div v-else class="text-slate-500 p-8">
                <Icon :name="fileIcon(activeFile()!.mime)" class="w-12 h-12 mx-auto" />
              </div>
              <button @click="activePreview = null" class="absolute top-3 right-3 p-1.5 rounded-full bg-black/60 text-white hover:bg-black/80">
                <Icon name="lucide:x" class="w-4 h-4" />
              </button>
            </div>
            <div class="p-4 space-y-3">
              <p class="text-xs text-slate-500 truncate">{{ activeFile()!.file.name }}</p>
              <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition"
                :class="activeFile()!.blur ? 'bg-indigo-600/20' : 'bg-slate-800/30 hover:bg-slate-800/50'">
                <input type="checkbox" :checked="activeFile()!.blur"
                  @change="pendingFiles[activePreview!].blur = !pendingFiles[activePreview!].blur"
                  class="w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                <div>
                  <p class="text-sm font-medium text-white">ぼかしをかける</p>
                  <p class="text-xs text-slate-500">閲覧者がクリックで表示できるぼかしを適用</p>
                </div>
              </label>
              <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition"
                :class="activeFile()!.watermark ? 'bg-indigo-600/20' : 'bg-slate-800/30 hover:bg-slate-800/50'">
                <input type="checkbox" :checked="activeFile()!.watermark"
                  @change="pendingFiles[activePreview!].watermark = !pendingFiles[activePreview!].watermark"
                  class="w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                <div>
                  <p class="text-sm font-medium text-white">ウォーターマーク</p>
                  <p class="text-xs text-slate-500">画像に@ユーザー名を透かしとして埋め込み</p>
                </div>
              </label>
            </div>
          </div>
        </div>
      </Teleport>

      <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <button @click="fileInput?.click()" :disabled="pendingFiles.length >= MAX_FILES || uploading"
            class="p-1.5 rounded-full text-slate-500 hover:text-indigo-400 hover:bg-slate-800/50 transition disabled:opacity-30"
            :title="`ファイル添付 (${pendingFiles.length}/${MAX_FILES})`">
            <Icon name="lucide:paperclip" class="w-4 h-4" />
          </button>
          <span v-if="pendingFiles.length" class="text-[11px] text-slate-600">{{ pendingFiles.length }}/{{ MAX_FILES }}</span>

          <div class="relative">
            <button @click="showPrivacy = !showPrivacy"
              class="p-1.5 rounded-full text-slate-500 hover:text-indigo-400 hover:bg-slate-800/50 transition text-xs flex items-center gap-1">
              <Icon :name="selectedVis?.icon || 'lucide:globe'" class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">{{ selectedVis?.label || '公開' }}</span>
            </button>
            <div v-if="showPrivacy" class="absolute bottom-full left-0 mb-1 bg-slate-900 border border-slate-800 rounded-xl py-1.5 shadow-xl z-50 min-w-44"
              @click.outside="showPrivacy = false">
              <button v-for="opt in visibilityOptions" :key="opt.key"
                @click="visibility = opt.key; showPrivacy = false"
                class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 transition"
                :class="visibility === opt.key ? 'text-indigo-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30'">
                <Icon :name="opt.icon" class="w-4 h-4" /> {{ opt.label }}
              </button>
            </div>
          </div>
        </div>

        <button @click="handleSubmit"
          :disabled="(!content.trim() && !pendingFiles.length) || uploading"
          class="px-5 py-1.5 rounded-full bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
          <Icon v-if="uploading" name="lucide:loader-2" class="w-3.5 h-3.5 animate-spin" />
          {{ uploading ? 'アップロード中...' : 'ポストする' }}
        </button>
      </div>

      <input ref="fileInput" type="file" multiple accept=".png,.jpeg,.jpg,.gif,.webp,.webm,.mp4,.mp3,.ogg" class="hidden" @change="onFileSelect" />
    </div>
  </div>
</template>
