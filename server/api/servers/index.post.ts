import { randomUUID } from 'crypto'
import { db, initDb } from '../../db'
import * as schema from '../../db/schema'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)
  if (!body.name?.trim()) throw createError({ statusCode: 400, message: 'サーバー名を入力してください' })

  const serverId = randomUUID()
  const now = new Date()

  const [server] = await db.insert(schema.servers).values({
    id: serverId,
    name: body.name,
    description: body.description || '',
    iconUrl: body.iconUrl || null,
    bannerUrl: body.bannerUrl || null,
    ownerId: user.id,
    isPublic: body.isPublic !== false,
    createdAt: now,
    updatedAt: now,
  }).returning()

  const roleId = randomUUID()
  await db.insert(schema.serverRoles).values({
    id: roleId,
    serverId,
    name: 'Admin',
    color: '#ff0000',
    position: 999,
    permissions: 'all',
    isAdmin: true,
  })

  await db.insert(schema.serverChannels).values({
    id: randomUUID(),
    serverId,
    name: 'general',
    type: 'text',
    position: 0,
  })

  await db.insert(schema.serverMembers).values({
    id: randomUUID(),
    serverId,
    userId: user.id,
    roleId,
    nickname: null,
    joinedAt: now,
  })

  return { server }
})
