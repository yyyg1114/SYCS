import { randomUUID } from 'crypto'
import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const { postId } = await readBody(event)
  if (!postId) throw createError({ statusCode: 400 })

  const existing = await db.query.bookmarks.findFirst({
    where: and(eq(schema.bookmarks.userId, user.id), eq(schema.bookmarks.postId, postId)),
  })

  if (existing) {
    await db.delete(schema.bookmarks).where(eq(schema.bookmarks.id, existing.id))
    return { bookmarked: false }
  }

  await db.insert(schema.bookmarks).values({ id: randomUUID(), userId: user.id, postId })
  return { bookmarked: true }
})
