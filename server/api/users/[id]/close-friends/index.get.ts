import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, inArray } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const userId = getRouterParam(event, 'id')

  const list = await db.query.closeFriends.findMany({
    where: eq(schema.closeFriends.userId, userId!),
    columns: { friendId: true },
  })

  const friendIds = list.map(cf => cf.friendId)
  const closeFriends = friendIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, friendIds) })
    : []

  return { closeFriends }
})
