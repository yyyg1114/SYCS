import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const id = getRouterParam(event, 'id')

  const server = await db.query.servers.findFirst({ where: eq(schema.servers.id, id) })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })
  if (server.ownerId !== user.id) throw createError({ statusCode: 403 })

  await db.delete(schema.servers).where(eq(schema.servers.id, id))

  return { success: true }
})
