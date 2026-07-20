import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const serverId = getRouterParam(event, 'id')

  const invites = await db.query.serverInvites.findMany({
    where: eq(schema.serverInvites.serverId, serverId),
  })

  return { invites }
})
