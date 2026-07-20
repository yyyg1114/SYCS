export default defineNuxtRouteMiddleware(async (to) => {
  if (to.path === '/') {
    try {
      await $fetch('/api/auth/me')
      return navigateTo('/home')
    } catch {
      // not authenticated → stay on landing
    }
  }
})
