import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq, and } from 'drizzle-orm'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const id = getRouterParam(event, 'id')

  const post = await db.query.posts.findFirst({ where: eq(schema.posts.id, id) })
  if (!post) throw createError({ statusCode: 404, message: '投稿が見つかりません' })
  if (post.userId !== user.id) throw createError({ statusCode: 403 })

  await db.delete(schema.posts).where(eq(schema.posts.id, id))
  return { success: true }
})
