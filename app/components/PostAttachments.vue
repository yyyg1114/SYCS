<script setup lang="ts">
const props = defineProps<{
  attachments: Array<{
    id: string
    url: string
    blurUrl?: string | null
    type: string
    mime: string
  }>
}>()

const blurred = ref<Set<string>>(new Set())
const watermarked = ref<Set<string>>(new Set())

function toggleBlur(id: string) {
  if (blurred.value.has(id)) blurred.value.delete(id)
  else blurred.value.add(id)
  blurred.value = new Set(blurred.value)
}

function toggleWatermark(id: string) {
  if (watermarked.value.has(id)) watermarked.value.delete(id)
  else watermarked.value.add(id)
  watermarked.value = new Set(watermarked.value)
}

function isImage(mime: string) {
  return mime.startsWith('image/')
}

function isVideo(mime: string) {
  return mime.startsWith('video/')
}

function isAudio(mime: string) {
  return mime.startsWith('audio/')
}
</script>

<template>
  <div v-if="attachments.length" class="mt-2 grid gap-1.5"
    :class="attachments.length === 1 ? 'grid-cols-1' : attachments.length <= 4 ? 'grid-cols-2' : 'grid-cols-3'">
    <div v-for="att in attachments" :key="att.id" class="relative group rounded-lg overflow-hidden bg-slate-900/50">
      <img v-if="isImage(att.mime)" :src="blurred.has(att.id) && att.blurUrl ? att.blurUrl : att.url"
        class="w-full h-48 object-cover transition duration-300"
        :class="{ 'blur-xl': blurred.has(att.id) && !att.blurUrl }" />

      <video v-else-if="isVideo(att.mime)" :src="att.url" controls
        class="w-full h-48 object-cover bg-black" />

      <audio v-else-if="isAudio(att.mime)" :src="att.url" controls
        class="w-full h-12 mt-4 mx-2" />

      <div v-if="isImage(att.mime)"
        class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
        <button v-if="att.blurUrl" @click="toggleBlur(att.id)"
          class="p-1.5 rounded-lg bg-black/60 text-white text-xs hover:bg-black/80 backdrop-blur-sm"
          :title="blurred.has(att.id) ? 'ぼかし解除' : 'ぼかし'">
          <Icon :name="blurred.has(att.id) ? 'lucide:eye' : 'lucide:eye-off'" class="w-3.5 h-3.5" />
        </button>
        <button @click="toggleWatermark(att.id)"
          class="p-1.5 rounded-lg bg-black/60 text-white text-xs hover:bg-black/80 backdrop-blur-sm"
          :title="watermarked.has(att.id) ? '透かし解除' : '透かし'">
          <Icon :name="watermarked.has(att.id) ? 'lucide:images' : 'lucide:file-image'" class="w-3.5 h-3.5" />
        </button>
      </div>

      <!-- Watermark overlay -->
      <div v-if="watermarked.has(att.id) && isImage(att.mime)"
        class="absolute inset-0 flex items-center justify-center pointer-events-none select-none">
        <span class="text-white/20 text-4xl font-bold -rotate-30 tracking-widest text-shadow">SYCS</span>
      </div>
    </div>
  </div>
</template>
