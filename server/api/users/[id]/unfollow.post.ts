import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const followingId = getRouterParam(event, 'id')

  await db.delete(schema.follows).where(
    and(eq(schema.follows.followerId, user.id), eq(schema.follows.followingId, followingId))
  )

  return { success: true }
})
