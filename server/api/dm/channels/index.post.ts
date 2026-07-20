import { randomUUID } from 'crypto'
import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const { participantId } = await readBody(event)
  if (!participantId) throw createError({ statusCode: 400, message: '参加者IDが必要です' })

  const channelId = randomUUID()
  await db.insert(schema.dmChannels).values({ id: channelId })
  await db.insert(schema.dmChannelMembers).values([
    { id: randomUUID(), channelId, userId: user.id },
    { id: randomUUID(), channelId, userId: participantId },
  ])

  return { channel: { id: channelId } }
})
