import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, and, sql } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const postId = getRouterParam(event, 'id')

  const existing = await db.query.likes.findFirst({
    where: and(eq(schema.likes.userId, user.id), eq(schema.likes.postId, postId))
  })
  if (!existing) return { success: true }

  await db.delete(schema.likes).where(and(eq(schema.likes.userId, user.id), eq(schema.likes.postId, postId)))
  await db.execute(sql`UPDATE posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ${postId}`)

  return { success: true }
})
