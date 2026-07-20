import { randomUUID } from 'crypto'
import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const friendId = getRouterParam(event, 'id')

  const existing = await db.query.closeFriends.findFirst({
    where: and(eq(schema.closeFriends.userId, user.id), eq(schema.closeFriends.friendId, friendId!)),
  })
  if (existing) throw createError({ statusCode: 409, message: '既に親しい友達です' })

  await db.insert(schema.closeFriends).values({
    id: randomUUID(), userId: user.id, friendId: friendId!,
  })

  return { success: true }
})
