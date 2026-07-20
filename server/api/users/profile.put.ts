import { db, initDb } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)

  const updates: Record<string, any> = {}
  if (body.displayName !== undefined) updates.displayName = body.displayName
  if (body.bio !== undefined) updates.bio = body.bio
  if (body.avatarUrl !== undefined) updates.avatarUrl = body.avatarUrl
  if (body.bannerUrl !== undefined) updates.bannerUrl = body.bannerUrl
  updates.updatedAt = new Date()

  const [updated] = await db.update(schema.users)
    .set(updates)
    .where(eq(schema.users.id, user.id))
    .returning()

  return { user: updated }
})
