import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const roleId = getRouterParam(event, 'roleId')

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  await db.delete(schema.serverRoles).where(
    and(eq(schema.serverRoles.id, roleId), eq(schema.serverRoles.serverId, serverId))
  )

  return { success: true }
})
