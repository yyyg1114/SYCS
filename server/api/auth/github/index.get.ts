export default defineEventHandler(async (event) => {
  const clientId = process.env.GITHUB_CLIENT_ID
  if (!clientId) {
    throw createError({ statusCode: 500, message: 'GitHub OAuth not configured' })
  }
  const redirectUri = `${getRequestProtocol(event)}://${getRequestHost(event)}/api/auth/github/callback`
  const url = `https://github.com/login/oauth/authorize?client_id=${clientId}&redirect_uri=${encodeURIComponent(redirectUri)}&scope=read:user,user:email`
  return sendRedirect(event, url)
})
