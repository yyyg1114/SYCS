import { randomUUID } from 'crypto'
import { db, initDb } from '../../../../../../db'
import * as schema from '../../../../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const channelId = getRouterParam(event, 'channelId')
  const body = await readBody(event)
  if (!body.content?.trim()) throw createError({ statusCode: 400, message: 'メッセージを入力してください' })

  const [message] = await db.insert(schema.channelMessages).values({
    id: randomUUID(),
    channelId,
    userId: user.id,
    content: body.content,
  }).returning()

  return { message }
})
