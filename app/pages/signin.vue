<script setup lang="ts">
definePageMeta({ layout: false })

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const error = ref('')
const loading = ref(false)

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    const res = await $fetch('/api/auth/signin', {
      method: 'POST',
      body: { email: email.value, password: password.value, rememberMe: rememberMe.value },
    })
    if (res.user) await navigateTo('/home')
  } catch (e: any) {
    error.value = e.data?.message || 'ログインに失敗しました'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#0b0f19] text-white flex items-center justify-center p-6">
    <div class="w-full max-w-sm space-y-8">
      <div class="text-center">
        <h1 class="text-4xl font-extrabold">SYCS<span class="text-indigo-500">.</span></h1>
        <p class="text-slate-400 mt-2">ログイン</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="error" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-sm text-red-400">
          {{ error }}
        </div>

        <div>
          <label class="block text-sm text-slate-400 mb-1">メールアドレス</label>
          <input v-model="email" type="email" required
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            placeholder="you@example.com" />
        </div>

        <div>
          <label class="block text-sm text-slate-400 mb-1">パスワード</label>
          <input v-model="password" type="password" required
            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            placeholder="********" />
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="rememberMe" class="w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-slate-800" />
          <span class="text-sm text-slate-400">ログインを保持する</span>
        </label>

        <button type="submit" :disabled="loading"
          class="w-full py-2.5 bg-indigo-600 rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
          {{ loading ? 'ログイン中...' : 'ログイン' }}
        </button>
      </form>

      <div class="relative">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-700" /></div>
        <div class="relative flex justify-center text-sm"><span class="bg-[#0b0f19] px-2 text-slate-500">または</span></div>
      </div>

      <div class="space-y-3">
        <a href="/api/auth/github"
          class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-800 border border-slate-700 rounded-lg font-medium hover:bg-slate-700 transition">
          <Icon name="mdi:github" class="text-xl" />
          GitHub でログイン
        </a>
        <a href="/api/auth/google"
          class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-800 border border-slate-700 rounded-lg font-medium hover:bg-slate-700 transition">
          <Icon name="mdi:google" class="text-xl" />
          Google でログイン
        </a>
      </div>

      <p class="text-center text-sm text-slate-500">
        アカウントをお持ちでない方は
        <NuxtLink to="/signup" class="text-indigo-400 hover:underline">新規登録</NuxtLink>
      </p>
    </div>
  </div>
</template>
