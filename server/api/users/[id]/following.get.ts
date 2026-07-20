import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, inArray } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  const follows = await db.query.follows.findMany({
    where: eq(schema.follows.followerId, id),
    columns: { followingId: true },
  })

  const followingIds = follows.map(f => f.followingId)
  const following = followingIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, followingIds) })
    : []

  return { following }
})
