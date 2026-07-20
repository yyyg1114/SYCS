import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, inArray } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const serverId = getRouterParam(event, 'id')

  const members = await db.query.serverMembers.findMany({
    where: eq(schema.serverMembers.serverId, serverId),
  })

  const userIds = [...new Set(members.map(m => m.userId))]
  const users = userIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, userIds) })
    : []
  const userMap = Object.fromEntries(users.map(u => [u.id, u]))

  const roleIds = [...new Set(members.map(m => m.roleId).filter(Boolean) as string[])]
  const roles = roleIds.length
    ? await db.query.serverRoles.findMany({ where: inArray(schema.serverRoles.id, roleIds) })
    : []
  const roleMap = Object.fromEntries(roles.map(r => [r.id, r]))

  const membersWithRelations = members.map(m => ({
    ...m,
    user: userMap[m.userId] || null,
    role: m.roleId ? roleMap[m.roleId] || null : null,
  }))

  return { members: membersWithRelations }
})
