import { requireAuth } from '../../utils/auth'
import { validateFile } from '../../utils/upload'
import { db } from '../../db'
import * as schema from '../../db/schema'
import { eq } from 'drizzle-orm'
import { randomUUID } from 'crypto'
import { extname, join } from 'path'
import { writeFile } from 'fs/promises'
import sharp from 'sharp'

const UPLOAD_DIR = join(process.cwd(), 'public', 'uploads')

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
    throw createError({ statusCode: 400, message: 'バナーは5MB以下にしてください' })
  }

  const ext = extname(file.filename!).toLowerCase()
  const name = `${randomUUID()}${ext}`
  const filePath = join(UPLOAD_DIR, name)

  await sharp(file.data!)
    .resize(1200, null, { fit: 'cover', withoutEnlargement: true })
    .toFile(filePath)

  const url = `/uploads/${name}`

  await db.update(schema.users)
    .set({ bannerUrl: url })
    .where(eq(schema.users.id, user.id))

  return { url }
})
