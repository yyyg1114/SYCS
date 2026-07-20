<script setup lang="ts">
const props = defineProps<{
  attachments: Array<{
    id: string
    url: string
    blurUrl?: string | null
    watermarkUrl?: string | null
    type: string
    mime: string
  }>
}>()

const blurredMap = ref<Record<string, boolean>>({})

// Initialize: blurred by default if blurUrl exists
for (const att of props.attachments) {
  if (att.blurUrl) blurredMap.value[att.id] = true
}

function toggleBlur(id: string) {
  blurredMap.value = { ...blurredMap.value, [id]: !blurredMap.value[id] }
}

function displayUrl(att: any) {
  // Always show watermarked version if it exists (viewers cannot remove)
  if (att.watermarkUrl) return att.watermarkUrl
  if (att.blurUrl && blurredMap.value[att.id]) return att.blurUrl
  return att.url
}

function isBlurred(att: any) {
  return att.blurUrl && blurredMap.value[att.id]
}

function isImage(mime: string) { return mime.startsWith('image/') }
function isVideo(mime: string) { return mime.startsWith('video/') }
function isAudio(mime: string) { return mime.startsWith('audio/') }
</script>

<template>
  <div v-if="attachments.length" class="mt-2 grid gap-1.5"
    :class="attachments.length === 1 ? 'grid-cols-1' : attachments.length <= 4 ? 'grid-cols-2' : 'grid-cols-3'">
    <div v-for="att in attachments" :key="att.id" class="relative group rounded-lg overflow-hidden bg-slate-900/50">
      <template v-if="isImage(att.mime)">
        <img :src="displayUrl(att)"
          class="w-full h-48 object-cover cursor-pointer transition duration-300"
          :class="{ 'blur-xl': isBlurred(att) }"
          @click="att.blurUrl ? toggleBlur(att.id) : undefined" />

        <div v-if="att.blurUrl && blurredMap[att.id]"
          class="absolute inset-0 flex items-center justify-center cursor-pointer"
          @click="toggleBlur(att.id)">
          <div class="bg-black/50 backdrop-blur-sm rounded-full px-4 py-2 text-white text-sm font-bold flex items-center gap-2">
            <Icon name="lucide:eye-off" class="w-4 h-4" />
            閲覧するにはクリック
          </div>
        </div>
      </template>

      <video v-else-if="isVideo(att.mime)" :src="att.url" controls
        class="w-full h-48 object-cover bg-black" />

      <audio v-else-if="isAudio(att.mime)" :src="att.url" controls
        class="w-full h-12 mt-4 mx-2" />
    </div>
  </div>
</template>
