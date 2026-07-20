import { db, initDb } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'
import { verifyPassword, createSession, setAuthCookie } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  await initDb()

  const body = await readBody(event)
  const { email, password } = body

  if (!email || !password) {
    throw createError({ statusCode: 400, message: 'メールアドレスとパスワードを入力してください' })
  }

  const user = await db.query.users.findFirst({
    where: eq(schema.users.email, email),
  })
  if (!user || !user.passwordHash) {
    throw createError({ statusCode: 401, message: 'メールアドレスまたはパスワードが正しくありません' })
  }

  const valid = await verifyPassword(password, user.passwordHash)
  if (!valid) {
    throw createError({ statusCode: 401, message: 'メールアドレスまたはパスワードが正しくありません' })
  }

  const { token } = await createSession(user.id)
  setAuthCookie(event, token)

  return {
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      displayName: user.displayName,
      avatarUrl: user.avatarUrl,
      bio: user.bio,
    },
  }
})
