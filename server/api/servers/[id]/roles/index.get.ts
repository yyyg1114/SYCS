import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, desc } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const serverId = getRouterParam(event, 'id')

  const roles = await db.query.serverRoles.findMany({
    where: eq(schema.serverRoles.serverId, serverId),
    orderBy: [desc(schema.serverRoles.position)],
  })

  return { roles }
})
