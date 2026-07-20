import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const memberUserId = getRouterParam(event, 'userId')

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })
  if (memberUserId === user.id) throw createError({ statusCode: 400, message: '自分自身をキックできません' })

  await db.delete(schema.serverMembers).where(
    and(eq(schema.serverMembers.serverId, serverId), eq(schema.serverMembers.userId, memberUserId))
  )

  return { success: true }
})
