<script setup lang="ts">
const emit = defineEmits<{
  submit: [content: string, attachments?: Array<{ url: string; blurUrl?: string | null; type: string; mime: string }>]
}>()

const content = ref('')
const files = ref<Array<{ file: File; preview: string; url?: string; blurUrl?: string | null; type: string; mime: string }>>([])
const uploading = ref(false)
const textareaRef = ref<HTMLTextAreaElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const MAX_FILES = 8
const ALLOWED = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'video/webm', 'video/mp4', 'audio/mpeg', 'audio/ogg']

function autoResize() {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  const maxHeight = 20 * 8
  el.style.height = `${Math.min(el.scrollHeight, maxHeight)}px`
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  const remaining = MAX_FILES - files.value.length
  const selected = Array.from(input.files).slice(0, remaining)
  for (const f of selected) {
    if (!ALLOWED.includes(f.type)) continue
    files.value.push({
      file: f,
      preview: URL.createObjectURL(f),
      url: undefined,
      blurUrl: undefined,
      type: f.type.startsWith('image/') ? 'image' : f.type.startsWith('video/') ? 'video' : 'audio',
      mime: f.type,
    })
  }
  input.value = ''
}

function removeFile(index: number) {
  const f = files.value[index]
  if (f.preview) URL.revokeObjectURL(f.preview)
  files.value.splice(index, 1)
}

async function handleSubmit() {
  if (!content.value.trim() && !files.value.length) return
  uploading.value = true
  try {
    let attachments: Array<{ url: string; blurUrl?: string | null; type: string; mime: string }> | undefined
    if (files.value.length) {
      const formData = new FormData()
      for (const f of files.value) {
        formData.append('files', f.file)
      }
      const res = await $fetch<{ files: Array<{ url: string; blurUrl: string | null; type: string; mime: string }> }>('/api/upload', {
        method: 'POST',
        body: formData,
      })
      attachments = res.files
    }
    emit('submit', content.value, attachments)
    content.value = ''
    for (const f of files.value) {
      if (f.preview) URL.revokeObjectURL(f.preview)
    }
    files.value = []
    nextTick(autoResize)
  } finally {
    uploading.value = false
  }
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
      <textarea
        ref="textareaRef"
        v-model="content"
        @input="autoResize"
        class="w-full bg-transparent border-none focus:ring-0 text-white placeholder-slate-500 resize-none text-sm leading-5"
        placeholder="なにかあった？"
        rows="2"
      />

      <!-- File previews -->
      <div v-if="files.length" class="flex flex-wrap gap-2">
        <div v-for="(f, i) in files" :key="i" class="relative w-20 h-20 rounded-lg overflow-hidden bg-slate-900/70 border border-slate-700">
          <img v-if="f.type === 'image'" :src="f.preview" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-slate-500">
            <Icon :name="fileIcon(f.mime)" class="w-6 h-6" />
          </div>
          <button @click="removeFile(i)" class="absolute top-0.5 right-0.5 p-0.5 rounded-full bg-black/60 text-white hover:bg-black/80">
            <Icon name="lucide:x" class="w-3 h-3" />
          </button>
          <div class="absolute bottom-0 left-0 right-0 text-[9px] text-white/70 bg-black/50 px-1 truncate text-center">
            {{ f.file.name.slice(0, 12) }}
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button @click="fileInput?.click()" :disabled="files.length >= MAX_FILES || uploading"
            class="p-1.5 rounded-full text-slate-500 hover:text-indigo-400 hover:bg-slate-800/50 transition disabled:opacity-30"
            :title="`ファイル添付 (${files.length}/${MAX_FILES})`">
            <Icon name="lucide:paperclip" class="w-4 h-4" />
          </button>
          <span v-if="files.length" class="text-[11px] text-slate-600">{{ files.length }}/{{ MAX_FILES }}</span>
        </div>
        <button
          @click="handleSubmit"
          :disabled="(!content.trim() && !files.length) || uploading"
          class="px-5 py-1.5 rounded-full bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
        >
          <Icon v-if="uploading" name="lucide:loader-2" class="w-3.5 h-3.5 animate-spin" />
          {{ uploading ? 'アップロード中...' : 'ポストする' }}
        </button>
      </div>

      <input ref="fileInput" type="file" multiple accept=".png,.jpeg,.jpg,.gif,.webp,.webm,.mp4,.mp3,.ogg" class="hidden" @change="onFileSelect" />
    </div>
  </div>
</template>
