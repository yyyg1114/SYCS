import { randomUUID } from 'crypto'
import { db, initDb } from '../../db'
import * as schema from '../../db/schema'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)
  if (!body.content?.trim()) throw createError({ statusCode: 400, message: '本文を入力してください' })

  const [post] = await db.insert(schema.posts).values({
    id: randomUUID(), userId: user.id, content: body.content,
    imageUrl: body.imageUrl || null,
  }).returning()

  return { post }
})
