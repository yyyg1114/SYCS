import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const serverId = getRouterParam(event, 'id')

  const channels = await db.query.serverChannels.findMany({
    where: eq(schema.serverChannels.serverId, serverId),
    orderBy: (channels, { asc }) => [asc(channels.position)],
  })

  return { channels }
})
