import { randomUUID } from 'crypto'
import { db } from '../../db'
import * as schema from '../../db/schema'
import { requireAuth } from '../../utils/auth'
import { emit } from '../../utils/eventBus'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)
  if (!body.content?.trim()) throw createError({ statusCode: 400, message: '本文を入力してください' })

  const postId = randomUUID()

  const [post] = await db.insert(schema.posts).values({
    id: postId, userId: user.id, content: body.content,
    imageUrl: body.imageUrl || null,
  }).returning()

  if (body.attachments?.length) {
    await db.insert(schema.postAttachments).values(
      body.attachments.map((a: any, i: number) => ({
        id: randomUUID(), postId, url: a.url,
        blurUrl: a.blurUrl || null, watermarkUrl: null,
        type: a.type || 'image', mime: a.mime || 'image/png',
        position: i,
      }))
    )
  }

  const postWithUser = { ...post, user, attachments: body.attachments || [], liked: false, reposted: false }
  emit('post:created', { post: postWithUser })

  return { post: postWithUser }
})
