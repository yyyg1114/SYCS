import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const roleId = getRouterParam(event, 'roleId')
  const body = await readBody(event)

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  const updates: Record<string, any> = {}
  if (body.name !== undefined) updates.name = body.name
  if (body.color !== undefined) updates.color = body.color
  if (body.position !== undefined) updates.position = body.position
  if (body.permissions !== undefined) updates.permissions = body.permissions
  if (body.isAdmin !== undefined) updates.isAdmin = body.isAdmin

  const [role] = await db.update(schema.serverRoles)
    .set(updates)
    .where(and(eq(schema.serverRoles.id, roleId), eq(schema.serverRoles.serverId, serverId)))
    .returning()

  return { role }
})
