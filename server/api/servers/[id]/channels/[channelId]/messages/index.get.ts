import { db } from '../../../../../../db'
import * as schema from '../../../../../../db/schema'
import { eq, asc, inArray } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const channelId = getRouterParam(event, 'channelId')
  const query = getQuery(event)
  const limit = Math.min(Number(query.limit) || 50, 100)
  const offset = Number(query.offset) || 0

  const messages = await db.query.channelMessages.findMany({
    where: eq(schema.channelMessages.channelId, channelId),
    limit, offset,
    orderBy: [asc(schema.channelMessages.createdAt)],
  })

  const userIds = [...new Set(messages.map(m => m.userId))]
  const users = userIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, userIds) })
    : []
  const userMap = Object.fromEntries(users.map(u => [u.id, u]))
  const messagesWithUser = messages.map(m => ({ ...m, user: userMap[m.userId] || null }))

  return { messages: messagesWithUser }
})
