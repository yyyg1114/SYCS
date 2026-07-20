import { randomUUID } from 'crypto'
import { db, initDb } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const serverId = getRouterParam(event, 'id')
  const body = await readBody(event)
  if (!body.name?.trim()) throw createError({ statusCode: 400, message: 'チャンネル名を入力してください' })

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, serverId) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  const [channel] = await db.insert(schema.serverChannels).values({
    id: randomUUID(),
    serverId,
    name: body.name,
    type: body.type || 'text',
    position: body.position || 0,
    description: body.description || '',
  }).returning()

  return { channel }
})
