import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const friendId = getRouterParam(event, 'id')

  await db.delete(schema.closeFriends).where(
    and(eq(schema.closeFriends.userId, user.id), eq(schema.closeFriends.friendId, friendId!))
  )

  return { success: true }
})
