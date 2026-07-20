import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, desc } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const query = getQuery(event)
  const limit = Math.min(Number(query.limit) || 50, 100)
  const offset = Number(query.offset) || 0

  // posts have user already resolved via a separate query
  const posts = await db.query.posts.findMany({
    where: eq(schema.posts.userId, id),
    limit, offset,
    orderBy: [desc(schema.posts.createdAt)],
  })

  return { posts }
})
