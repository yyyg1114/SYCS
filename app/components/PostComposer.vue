<script setup lang="ts">
const emit = defineEmits<{
  submit: [content: string, imageUrl?: string]
}>()

const content = ref('')
const imageUrl = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

function autoResize() {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  const lineHeight = 20
  const maxHeight = lineHeight * 8
  el.style.height = `${Math.min(el.scrollHeight, maxHeight)}px`
}

function handleSubmit() {
  if (!content.value.trim()) return
  emit('submit', content.value, imageUrl.value || undefined)
  content.value = ''
  imageUrl.value = ''
  nextTick(autoResize)
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
      <input
        v-model="imageUrl"
        type="text"
        placeholder="画像URL (任意)"
        class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-slate-300 placeholder-slate-600 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
      />
      <div class="flex justify-end">
        <button
          @click="handleSubmit"
          :disabled="!content.trim()"
          class="px-5 py-1.5 rounded-full bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          ポストする
        </button>
      </div>
    </div>
  </div>
</template>
