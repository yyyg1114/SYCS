import { randomUUID } from 'crypto'
import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, sql } from 'drizzle-orm'
import { getCurrentUser } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const postId = getRouterParam(event, 'id')
  const user = await getCurrentUser(event)

  await db.insert(schema.postViews).values({
    id: randomUUID(),
    postId,
    userId: user?.id || null,
  })

  await db.execute(sql`UPDATE posts SET view_count = view_count + 1 WHERE id = ${postId}`)

  return { success: true }
})
