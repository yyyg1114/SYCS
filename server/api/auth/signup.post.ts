import { randomUUID } from 'crypto'
import { db, initDb } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'
import { hashPassword, createSession, setAuthCookie } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  await initDb()

  const body = await readBody(event)
  const { email, username, displayName, password } = body

  if (!email || !username || !displayName || !password) {
    throw createError({ statusCode: 400, message: '全ての必須項目を入力してください' })
  }

  if (password.length < 8) {
    throw createError({ statusCode: 400, message: 'パスワードは8文字以上必要です' })
  }

  const existingUser = await db.query.users.findFirst({
    where: eq(schema.users.email, email),
  })
  if (existingUser) {
    throw createError({ statusCode: 409, message: 'このメールアドレスは既に登録されています' })
  }

  const existingUsername = await db.query.users.findFirst({
    where: eq(schema.users.username, username),
  })
  if (existingUsername) {
    throw createError({ statusCode: 409, message: 'このユーザー名は既に使用されています' })
  }

  const now = new Date()
  const userId = randomUUID()
  const passwordHash = await hashPassword(password)

  await db.insert(schema.users).values({
    id: userId,
    email,
    username,
    displayName,
    passwordHash,
    avatarUrl: null,
    bio: '',
    createdAt: now,
    updatedAt: now,
  })

  const { token } = await createSession(userId, true)
  setAuthCookie(event, token, true)

  return { user: { id: userId, email, username, displayName } }
})
