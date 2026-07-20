import { randomUUID } from 'crypto'
import { readFile } from 'fs/promises'
import { join, extname } from 'path'
import { db } from '../../db'
import * as schema from '../../db/schema'
import { requireAuth } from '../../utils/auth'
import { saveFileWithWatermark } from '../../utils/upload'
import { emit } from '../../utils/eventBus'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readBody(event)
  if (!body.content?.trim() && !body.attachments?.length) {
    throw createError({ statusCode: 400, message: '本文またはファイルを入力してください' })
  }

  const postId = randomUUID()

  const [post] = await db.insert(schema.posts).values({
    id: postId,
    userId: user.id,
    content: body.content || '',
    imageUrl: body.imageUrl || null,
    visibility: body.visibility || 'public',
    visibleTo: body.visibleTo ? JSON.stringify(body.visibleTo) : '[]',
  }).returning()

  const values: any[] = []

  if (body.attachments?.length) {
    for (const [i, a] of body.attachments.entries()) {
      const ext = extname(a.url)
      let url = a.url
      let blurUrl = (a.blur && a.blurUrl) || null
      let watermarkUrl: string | null = null

      if (a.watermark && IMAGE_EXTENSIONS.includes(ext)) {
        try {
          const filePath = join(process.cwd(), 'public', a.url)
          const buffer = await readFile(filePath)
          const { url: newUrl, blurUrl: newBlur, watermarkUrl: newWm } = await saveFileWithWatermark(buffer, `wm_${a.url.replace('/uploads/', '')}`, user.username)
          url = newUrl
          blurUrl = newBlur
          watermarkUrl = newWm
        } catch {}
      }

      values.push({
        id: randomUUID(), postId, url, blurUrl, watermarkUrl,
        type: a.type || 'image', mime: a.mime || 'image/png',
        position: i,
      })
    }
    await db.insert(schema.postAttachments).values(values)
  }

  const postWithUser = { ...post, user, attachments: values, liked: false, reposted: false, bookmarked: false }
  emit('post:created', { post: postWithUser })

  return { post: postWithUser }
})

const IMAGE_EXTENSIONS = ['.png', '.jpeg', '.jpg', '.gif', '.webp']
