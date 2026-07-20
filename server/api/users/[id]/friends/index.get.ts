import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, or, and, inArray } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const userId = getRouterParam(event, 'id')

  const friendList = await db.query.friends.findMany({
    where: and(
      or(eq(schema.friends.userId, userId!), eq(schema.friends.friendId, userId!)),
      eq(schema.friends.status, 'accepted')
    ),
  })

  const userIds = friendList.map(f => f.userId === userId ? f.friendId : f.userId)
  const users = await db.query.users.findMany({
    where: inArray(schema.users.id, userIds),
  })

  return { friends: users }
})
