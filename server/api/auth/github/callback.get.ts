import { randomUUID } from 'crypto'
import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq } from 'drizzle-orm'
import { createSession, setAuthCookie } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  await initDb()

  const query = getQuery(event)
  const { code, state } = query

  if (!code || !state) {
    throw createError({ statusCode: 400, message: 'Invalid OAuth callback' })
  }

  const clientId = process.env.GITHUB_CLIENT_ID
  const clientSecret = process.env.GITHUB_CLIENT_SECRET

  if (!clientId || !clientSecret) {
    throw createError({ statusCode: 500, message: 'GitHub OAuth not configured' })
  }

  const tokenResponse = await fetch('https://github.com/login/oauth/access_token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ client_id: clientId, client_secret: clientSecret, code }),
  })
  const tokenData: any = await tokenResponse.json()

  if (!tokenData.access_token) {
    throw createError({ statusCode: 401, message: 'Failed to get access token' })
  }

  const userResponse = await fetch('https://api.github.com/user', {
    headers: { Authorization: `Bearer ${tokenData.access_token}` },
  })
  const githubUser: any = await userResponse.json()

  const existingAccount = await db.query.accounts.findFirst({
    where: eq(schema.accounts.providerAccountId, String(githubUser.id)),
  })

  if (existingAccount) {
    const { token } = await createSession(existingAccount.userId)
    setAuthCookie(event, token)
    return sendRedirect(event, '/home')
  }

  const existingUser = githubUser.email
    ? await db.query.users.findFirst({ where: eq(schema.users.email, githubUser.email) })
    : null

  let userId: string

  if (existingUser) {
    userId = existingUser.id
  } else {
    userId = randomUUID()
    const now = new Date()
    const baseUsername = (githubUser.login || `user_${randomUUID().slice(0, 8)}`).toLowerCase()
    let username = baseUsername
    let counter = 1
    while (await db.query.users.findFirst({ where: eq(schema.users.username, username) })) {
      username = `${baseUsername}${counter++}`
    }

    await db.insert(schema.users).values({
      id: userId,
      email: githubUser.email || `${username}@github.placeholder`,
      username,
      displayName: githubUser.name || githubUser.login,
      passwordHash: null,
      avatarUrl: githubUser.avatar_url,
      bio: githubUser.bio || '',
      createdAt: now,
      updatedAt: now,
    })
  }

  await db.insert(schema.accounts).values({
    id: randomUUID(),
    userId,
    provider: 'github',
    providerAccountId: String(githubUser.id),
    providerAccessToken: tokenData.access_token,
    createdAt: new Date(),
  })

  const { token } = await createSession(userId)
  setAuthCookie(event, token)
  return sendRedirect(event, '/')
})
