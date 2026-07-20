<script setup lang="ts">
definePageMeta({ layout: false })

const { data: me } = await useFetch('/api/auth/me', { key: 'landing-auth' })
onMounted(() => {
  if (me.value?.user) navigateTo('/home')
})
</script>

<template>
  <div class="min-h-screen bg-[#0b0f19] text-white flex flex-col items-center justify-center p-6">
    <div class="max-w-xl text-center space-y-8">
      <h1 class="text-6xl font-extrabold tracking-tighter">SYCS<span class="text-indigo-500">.</span></h1>
      <p class="text-xl text-slate-400">次世代の分散型コミュニティ体験を、ここから。</p>
      <div class="flex gap-4 justify-center">
        <NuxtLink to="/signup" class="px-8 py-3 bg-indigo-600 rounded-full font-bold hover:bg-indigo-700 transition">新規登録</NuxtLink>
        <NuxtLink to="/signin" class="px-8 py-3 bg-slate-800 rounded-full font-bold hover:bg-slate-700 transition">ログイン</NuxtLink>
      </div>
    </div>
  </div>
</template>
