import { requireAuth } from '../../utils/auth'
import { validateFile, saveAvatar } from '../../utils/upload'
import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readMultipartFormData(event)
  if (!body?.length) throw createError({ statusCode: 400, message: 'ファイルがありません' })

  const file = body.find(p => p.filename && p.data)
  if (!file) throw createError({ statusCode: 400 })

  validateFile(file.filename!, file.type || '', file.data!)

  if (!file.type?.startsWith('image/')) {
    throw createError({ statusCode: 400, message: '画像ファイルのみアップロード可能です' })
  }

  if (file.data!.length > 5 * 1024 * 1024) {
    throw createError({ statusCode: 400, message: 'アバターは5MB以下にしてください' })
  }

  const url = await saveAvatar(file.data!, file.filename!)

  await db.update(schema.users)
    .set({ avatarUrl: url })
    .where(eq(schema.users.id, user.id))

  return { url }
})
