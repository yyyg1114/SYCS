import { randomUUID } from 'crypto'
import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq } from 'drizzle-orm'
import { createSession, setAuthCookie } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  await initDb()

  const query = getQuery(event)
  const { code } = query

  if (!code) {
    throw createError({ statusCode: 400, message: 'Invalid OAuth callback' })
  }

  const clientId = process.env.GOOGLE_CLIENT_ID
  const clientSecret = process.env.GOOGLE_CLIENT_SECRET

  if (!clientId || !clientSecret) {
    throw createError({ statusCode: 500, message: 'Google OAuth not configured' })
  }

  const redirectUri = `${getRequestProtocol(event)}://${getRequestHost(event)}/api/auth/google/callback`

  const tokenResponse = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      code: code as string,
      client_id: clientId,
      client_secret: clientSecret,
      redirect_uri: redirectUri,
      grant_type: 'authorization_code',
    }),
  })
  const tokenData: any = await tokenResponse.json()

  if (!tokenData.access_token) {
    throw createError({ statusCode: 401, message: 'Failed to get access token' })
  }

  const userResponse = await fetch('https://www.googleapis.com/oauth2/v2/userinfo', {
    headers: { Authorization: `Bearer ${tokenData.access_token}` },
  })
  const googleUser: any = await userResponse.json()

  const existingAccount = await db.query.accounts.findFirst({
    where: eq(schema.accounts.providerAccountId, googleUser.id),
  })

  if (existingAccount) {
    const { token } = await createSession(existingAccount.userId)
    setAuthCookie(event, token)
    return sendRedirect(event, '/home')
  }

  const existingUser = googleUser.email
    ? await db.query.users.findFirst({ where: eq(schema.users.email, googleUser.email) })
    : null

  let userId: string

  if (existingUser) {
    userId = existingUser.id
  } else {
    userId = randomUUID()
    const now = new Date()
    const baseUsername = (googleUser.email?.split('@')[0] || `user_${randomUUID().slice(0, 8)}`).toLowerCase()
    let username = baseUsername
    let counter = 1
    while (await db.query.users.findFirst({ where: eq(schema.users.username, username) })) {
      username = `${baseUsername}${counter++}`
    }

    await db.insert(schema.users).values({
      id: userId,
      email: googleUser.email,
      username,
      displayName: googleUser.name || googleUser.given_name || username,
      passwordHash: null,
      avatarUrl: googleUser.picture,
      bio: '',
      createdAt: now,
      updatedAt: now,
    })
  }

  await db.insert(schema.accounts).values({
    id: randomUUID(),
    userId,
    provider: 'google',
    providerAccountId: googleUser.id,
    providerAccessToken: tokenData.access_token,
    providerRefreshToken: tokenData.refresh_token,
    createdAt: new Date(),
  })

  const { token } = await createSession(userId)
  setAuthCookie(event, token)
  return sendRedirect(event, '/')
})
