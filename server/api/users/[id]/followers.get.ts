import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, inArray } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  const follows = await db.query.follows.findMany({
    where: eq(schema.follows.followingId, id),
    columns: { followerId: true },
  })

  const followerIds = follows.map(f => f.followerId)
  const followers = followerIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, followerIds) })
    : []

  return { followers }
})
