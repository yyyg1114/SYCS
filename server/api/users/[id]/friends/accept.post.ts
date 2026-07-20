import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const friendId = getRouterParam(event, 'id')

  const req = await db.query.friends.findFirst({
    where: and(eq(schema.friends.userId, friendId!), eq(schema.friends.friendId, user.id), eq(schema.friends.status, 'pending')),
  })
  if (!req) throw createError({ statusCode: 404, message: 'フレンドリクエストが見つかりません' })

  await db.update(schema.friends).set({ status: 'accepted', updatedAt: new Date() }).where(eq(schema.friends.id, req.id))

  return { success: true }
})
