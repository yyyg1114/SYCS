import { randomUUID } from 'crypto'
import { db, initDb } from '../../../db'
import * as schema from '../../../db/schema'
import { eq } from 'drizzle-orm'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const code = getRouterParam(event, 'code')

  const invite = await db.query.serverInvites.findFirst({ where: eq(schema.serverInvites.code, code) })
  if (!invite) throw createError({ statusCode: 404, message: '招待コードが見つかりません' })

  if (invite.expiresAt && new Date() > invite.expiresAt) {
    throw createError({ statusCode: 410, message: '招待コードの有効期限が切れています' })
  }

  if (invite.maxUses > 0 && invite.useCount >= invite.maxUses) {
    throw createError({ statusCode: 410, message: '招待コードの使用回数が上限に達しました' })
  }

  const existing = await db.query.serverMembers.findFirst({
    where: (members, { and }) => and(
      eq(members.serverId, invite.serverId),
      eq(members.userId, user.id)
    )
  })
  if (existing) throw createError({ statusCode: 409, message: '既にこのサーバーに参加しています' })

  await db.insert(schema.serverMembers).values({
    id: randomUUID(),
    serverId: invite.serverId,
    userId: user.id,
    joinedAt: new Date(),
  })

  await db.update(schema.serverInvites)
    .set({ useCount: invite.useCount + 1 })
    .where(eq(schema.serverInvites.id, invite.id))

  return { success: true }
})
