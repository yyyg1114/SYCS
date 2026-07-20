import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, id) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  const updates: Record<string, any> = {}
  if (body.name !== undefined) updates.name = body.name
  if (body.description !== undefined) updates.description = body.description
  if (body.iconUrl !== undefined) updates.iconUrl = body.iconUrl
  if (body.bannerUrl !== undefined) updates.bannerUrl = body.bannerUrl
  if (body.isPublic !== undefined) updates.isPublic = body.isPublic
  updates.updatedAt = new Date()

  const [updated] = await db.update(schema.servers)
    .set(updates)
    .where(eq(schema.servers.id, id))
    .returning()

  return { server: updated }
})
