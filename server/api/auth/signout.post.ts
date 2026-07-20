import { clearAuthCookie } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  clearAuthCookie(event)
  return { success: true }
})
