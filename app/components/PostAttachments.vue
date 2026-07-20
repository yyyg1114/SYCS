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

const blurredMap = ref<Record<string, boolean>>({})
const watermarkMap = ref<Record<string, boolean>>({})

// Watermark state determined by uploader (stored in db)
// Here we check if the attachment has a watermarkUrl set
// For now, we let the viewer toggle but the watermark is always rendered on the image server-side

function isBlurred(id: string) {
  return blurredMap.value[id] ?? true // default blurred if blurUrl exists
}

function toggleBlur(id: string) {
  blurredMap.value = { ...blurredMap.value, [id]: !isBlurred(id) }
}

function isImage(mime: string) { return mime.startsWith('image/') }
function isVideo(mime: string) { return mime.startsWith('video/') }
function isAudio(mime: string) { return mime.startsWith('audio/') }
</script>

<template>
  <div v-if="attachments.length" class="mt-2 grid gap-1.5"
    :class="attachments.length === 1 ? 'grid-cols-1' : attachments.length <= 4 ? 'grid-cols-2' : 'grid-cols-3'">
    <div v-for="att in attachments" :key="att.id" class="relative group rounded-lg overflow-hidden bg-slate-900/50">
      <!-- Image -->
      <template v-if="isImage(att.mime)">
        <img :src="att.blurUrl && isBlurred(att.id) ? att.blurUrl : att.url"
          class="w-full h-48 object-cover cursor-pointer transition duration-300"
          :class="{ 'blur-xl': !att.blurUrl && isBlurred(att.id) }"
          @click="att.blurUrl ? toggleBlur(att.id) : undefined" />

        <!-- Blur reveal overlay -->
        <div v-if="att.blurUrl && isBlurred(att.id)"
          class="absolute inset-0 flex items-center justify-center cursor-pointer"
          @click="toggleBlur(att.id)">
          <div class="bg-black/50 backdrop-blur-sm rounded-full px-4 py-2 text-white text-sm font-bold flex items-center gap-2">
            <Icon name="lucide:eye-off" class="w-4 h-4" />
            閲覧するにはクリック
          </div>
        </div>
      </template>

      <!-- Video -->
      <video v-else-if="isVideo(att.mime)" :src="att.url" controls
        class="w-full h-48 object-cover bg-black" />

      <!-- Audio -->
      <audio v-else-if="isAudio(att.mime)" :src="att.url" controls
        class="w-full h-12 mt-4 mx-2" />

      <!-- Watermark overlay (viewers can NEVER remove this) -->
      <div v-if="isImage(att.mime)"
        class="absolute inset-0 pointer-events-none select-none overflow-hidden">
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="text-white/10 text-3xl font-bold -rotate-30 tracking-widest"
            style="text-shadow: 0 0 4px rgba(0,0,0,0.5); font-size: min(3rem, 10vw);">
            SYCS
          </span>
        </div>
        <!-- Repeat watermark across image -->
        <div class="absolute top-2 left-2 text-white/20 text-[8px] font-bold rotate-30">SYCS</div>
        <div class="absolute top-2 right-2 text-white/20 text-[8px] font-bold -rotate-30">SYCS</div>
        <div class="absolute bottom-2 left-2 text-white/20 text-[8px] font-bold -rotate-30">SYCS</div>
        <div class="absolute bottom-2 right-2 text-white/20 text-[8px] font-bold rotate-30">SYCS</div>
      </div>
    </div>
  </div>
</template>
