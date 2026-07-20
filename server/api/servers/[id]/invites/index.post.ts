import { randomUUID } from 'crypto'
import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  const code = body.code || randomUUID().replace(/-/g, '').slice(0, 8)

  const [invite] = await db.insert(schema.serverInvites).values({
    id: randomUUID(),
    serverId,
    code,
    createdBy: user.id,
    maxUses: body.maxUses || 0,
    useCount: 0,
    expiresAt: body.expiresAt ? new Date(body.expiresAt) : null,
  }).returning()

  return { invite }
})
