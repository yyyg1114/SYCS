import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)

  const existing = JSON.parse(user.settings || '{}')
  const merged = { ...existing, ...body }

  await db.update(schema.users)
    .set({ settings: JSON.stringify(merged) })
    .where(eq(schema.users.id, user.id))

  return { settings: merged }
})
