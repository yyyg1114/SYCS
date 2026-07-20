import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, inArray } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)

  const memberships = await db.query.dmChannelMembers.findMany({
    where: eq(schema.dmChannelMembers.userId, user.id),
    columns: { channelId: true },
  })

  if (!memberships.length) return { channels: [] }

  const channelIds = memberships.map(m => m.channelId)

  const allMembers = await db.query.dmChannelMembers.findMany({
    where: inArray(schema.dmChannelMembers.channelId, channelIds),
  })

  const memberUserIds = [...new Set(allMembers.map(m => m.userId))]
  const memberUsers = memberUserIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, memberUserIds) })
    : []
  const memberUserMap = Object.fromEntries(memberUsers.map(u => [u.id, u]))

  const channels = await db.query.dmChannels.findMany({
    where: inArray(schema.dmChannels.id, channelIds),
    orderBy: (c, { desc }) => [desc(c.updatedAt)],
  })

  const result = channels.map(ch => ({
    ...ch,
    members: allMembers.filter(m => m.channelId === ch.id).map(m => memberUserMap[m.userId]).filter(Boolean),
  }))

  return { channels: result }
})
