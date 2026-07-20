import { randomUUID } from 'crypto'
import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const body = await readBody(event)
  if (!body.name?.trim()) throw createError({ statusCode: 400, message: 'ロール名を入力してください' })

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  const [role] = await db.insert(schema.serverRoles).values({
    id: randomUUID(),
    serverId,
    name: body.name,
    color: body.color || '#99aab5',
    position: body.position || 0,
    permissions: body.permissions || '',
    isAdmin: body.isAdmin || false,
  }).returning()

  return { role }
})
