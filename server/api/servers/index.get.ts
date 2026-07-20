import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq, inArray } from 'drizzle-orm'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)

  const memberships = await db.query.serverMembers.findMany({
    where: eq(schema.serverMembers.userId, user.id),
    columns: { serverId: true },
  })

  if (!memberships.length) return { servers: [] }

  const serverIds = memberships.map(m => m.serverId)
  const servers = await db.query.servers.findMany({
    where: inArray(schema.servers.id, serverIds),
  })

  return { servers }
})
