import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, count } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const username = getRouterParam(event, 'username')
  const user = await db.query.users.findFirst({ where: eq(schema.users.username, username) })
  if (!user) throw createError({ statusCode: 404, message: 'ユーザーが見つかりません' })

  const [followers] = await db.select({ count: count() }).from(schema.follows).where(eq(schema.follows.followingId, user.id))
  const [following] = await db.select({ count: count() }).from(schema.follows).where(eq(schema.follows.followerId, user.id))
  const [postsCount] = await db.select({ count: count() }).from(schema.posts).where(eq(schema.posts.userId, user.id))

  return { user, stats: { followers: followers.count, following: following.count, posts: postsCount.count } }
})
