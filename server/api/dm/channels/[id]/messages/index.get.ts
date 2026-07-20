import { db } from '../../../../../db'
import * as schema from '../../../../../db/schema'
import { eq, and, desc, inArray } from 'drizzle-orm'
import { requireAuth } from '../../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const channelId = getRouterParam(event, 'id')

  const membership = await db.query.dmChannelMembers.findFirst({
    where: and(eq(schema.dmChannelMembers.channelId, channelId!), eq(schema.dmChannelMembers.userId, user.id)),
  })
  if (!membership) throw createError({ statusCode: 403, message: 'このチャンネルにアクセスできません' })

  const messages = await db.query.dmMessages.findMany({
    where: eq(schema.dmMessages.channelId, channelId!),
    orderBy: [desc(schema.dmMessages.createdAt)],
    limit: 100,
  })

  const senderIds = [...new Set(messages.map(m => m.senderId))]
  const senders = senderIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, senderIds) })
    : []
  const senderMap = Object.fromEntries(senders.map(s => [s.id, s]))
  const messagesWithSenders = messages.map(m => ({ ...m, sender: senderMap[m.senderId] || null }))

  return { messages: messagesWithSenders.reverse() }
})
