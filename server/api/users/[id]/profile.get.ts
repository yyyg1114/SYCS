import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, count } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const user = await db.query.users.findFirst({ where: eq(schema.users.id, id) })
  if (!user) throw createError({ statusCode: 404 })

  const [followers] = await db.select({ count: count() }).from(schema.follows).where(eq(schema.follows.followingId, id))
  const [following] = await db.select({ count: count() }).from(schema.follows).where(eq(schema.follows.followerId, id))
  const [postsCount] = await db.select({ count: count() }).from(schema.posts).where(eq(schema.posts.userId, id))

  return { user, stats: { followers: followers.count, following: following.count, posts: postsCount.count } }
})
