import { randomUUID } from 'crypto'
import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, and, sql } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const postId = getRouterParam(event, 'id')

  const existing = await db.query.reposts.findFirst({
    where: and(eq(schema.reposts.userId, user.id), eq(schema.reposts.postId, postId))
  })
  if (existing) return { success: true }

  await db.insert(schema.reposts).values({ id: randomUUID(), userId: user.id, postId })
  await db.execute(sql`UPDATE posts SET repost_count = repost_count + 1 WHERE id = ${postId}`)

  return { success: true }
})
