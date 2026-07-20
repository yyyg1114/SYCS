import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, inArray } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  const server = await db.query.servers.findFirst({
    where: eq(schema.servers.id, id),
  })
  if (!server) throw createError({ statusCode: 404, message: 'サーバーが見つかりません' })

  const [channels, members, roles] = await Promise.all([
    db.query.serverChannels.findMany({ where: eq(schema.serverChannels.serverId, id) }),
    db.query.serverMembers.findMany({ where: eq(schema.serverMembers.serverId, id) }),
    db.query.serverRoles.findMany({ where: eq(schema.serverRoles.serverId, id) }),
  ])

  const userIds = members.map(m => m.userId)
  const users = userIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, userIds) })
    : []

  const userMap = Object.fromEntries(users.map(u => [u.id, u]))
  const membersWithUser = members.map(m => ({ ...m, user: userMap[m.userId] || null }))

  return { server, channels, members: membersWithUser, roles }
})
