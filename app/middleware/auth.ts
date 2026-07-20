export default defineNuxtRouteMiddleware(async (to, from) => {
  try {
    const data = await $fetch('/api/auth/me')
    if (!data.user) throw new Error()
  } catch {
    return navigateTo('/signin')
  }
})
