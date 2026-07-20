import { randomUUID } from 'crypto'
import { db } from '../../../../db'
import * as schema from '../../../../db/schema'
import { eq, and, or } from 'drizzle-orm'
import { requireAuth } from '../../../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const friendId = getRouterParam(event, 'id')
  if (friendId === user.id) throw createError({ statusCode: 400, message: '自分自身とフレンドにはなれません' })

  const existing = await db.query.friends.findFirst({
    where: or(
      and(eq(schema.friends.userId, user.id), eq(schema.friends.friendId, friendId!)),
      and(eq(schema.friends.userId, friendId!), eq(schema.friends.friendId, user.id))
    ),
  })
  if (existing) throw createError({ statusCode: 409, message: '既にフレンドリクエストを送信済みです' })

  await db.insert(schema.friends).values({
    id: randomUUID(), userId: user.id, friendId: friendId!, status: 'pending',
  })

  return { success: true }
})
