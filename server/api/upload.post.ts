import { requireAuth } from '../utils/auth'
import { validateFile, saveFile } from '../utils/upload'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)
  const body = await readMultipartFormData(event)
  if (!body?.length) throw createError({ statusCode: 400, message: 'ファイルがありません' })

  const files = body.filter(p => p.filename && p.data)
  if (files.length > 8) throw createError({ statusCode: 400, message: 'ファイルは最大8個までです' })

  const results = []
  for (const file of files) {
    validateFile(file.filename!, file.type || '', file.data!)
    const { url, blurUrl } = await saveFile(file.data!, file.filename!)
    results.push({ url, blurUrl, type: file.type, name: file.filename })
  }

  return { files: results }
})
