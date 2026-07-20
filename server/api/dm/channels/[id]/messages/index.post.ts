import { randomUUID } from 'crypto'
import { db } from '../../../../../db'
import * as schema from '../../../../../db/schema'
import { eq, and, inArray } from 'drizzle-orm'
import { requireAuth } from '../../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const channelId = getRouterParam(event, 'id')
  const { content } = await readBody(event)
  if (!content?.trim()) throw createError({ statusCode: 400, message: 'メッセージを入力してください' })

  const membership = await db.query.dmChannelMembers.findFirst({
    where: and(eq(schema.dmChannelMembers.channelId, channelId!), eq(schema.dmChannelMembers.userId, user.id)),
  })
  if (!membership) throw createError({ statusCode: 403, message: 'このチャンネルにアクセスできません' })

  const msgId = randomUUID()
  await db.insert(schema.dmMessages).values({
    id: msgId, channelId: channelId!, senderId: user.id, content,
  })
  await db.update(schema.dmChannels).set({ updatedAt: new Date() }).where(eq(schema.dmChannels.id, channelId!))

  const message = await db.query.dmMessages.findFirst({
    where: eq(schema.dmMessages.id, msgId),
  })

  const sender = message
    ? await db.query.users.findFirst({ where: eq(schema.users.id, message.senderId) })
    : null

  return { message: message ? { ...message, sender } : null }
})
