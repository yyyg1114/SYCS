import { randomUUID } from 'crypto'
import { writeFile, mkdir, rename } from 'fs/promises'
import { join, extname } from 'path'
import sharp from 'sharp'

const ALLOWED = ['.png', '.jpeg', '.jpg', '.gif', '.webp', '.webm', '.mp4', '.mp3', '.ogg']
const IMAGE_TYPES = ['.png', '.jpeg', '.jpg', '.gif', '.webp']

const MAX_SIZE_IMAGE = 10 * 1024 * 1024
const MAX_SIZE_VIDEO = 50 * 1024 * 1024
const MAX_SIZE_AUDIO = 30 * 1024 * 1024

const UPLOAD_DIR = join(process.cwd(), 'public', 'uploads')

export async function ensureDir() {
  await mkdir(UPLOAD_DIR, { recursive: true })
}

export function validateFile(filename: string, type: string, buffer: Buffer) {
  const ext = extname(filename).toLowerCase()
  if (!ALLOWED.includes(ext)) {
    throw createError({ statusCode: 400, message: `許可されていないファイル形式です: ${ext}` })
  }
  if (type.startsWith('image/') && buffer.length > MAX_SIZE_IMAGE) {
    throw createError({ statusCode: 400, message: '画像は10MB以下にしてください' })
  }
  if (type.startsWith('video/') && buffer.length > MAX_SIZE_VIDEO) {
    throw createError({ statusCode: 400, message: '動画は50MB以下にしてください' })
  }
  if (type.startsWith('audio/') && buffer.length > MAX_SIZE_AUDIO) {
    throw createError({ statusCode: 400, message: '音声は30MB以下にしてください' })
  }
}

export async function saveFile(buffer: Buffer, filename: string): Promise<{ url: string; blurUrl: string | null }> {
  await ensureDir()
  const ext = extname(filename).toLowerCase()
  const name = `${randomUUID()}${ext}`
  const filePath = join(UPLOAD_DIR, name)
  await writeFile(filePath, buffer)

  let blurUrl: string | null = null
  if (IMAGE_TYPES.includes(ext)) {
    try {
      const blurName = `${randomUUID()}-blur${ext}`
      const blurPath = join(UPLOAD_DIR, blurName)
      await sharp(buffer).blur(40).jpeg({ quality: 30 }).toFile(blurPath)
      blurUrl = `/uploads/${blurName}`
    } catch {}
  }

  return { url: `/uploads/${name}`, blurUrl }
}

export async function saveFileWithWatermark(buffer: Buffer, filename: string, username: string): Promise<{ url: string; blurUrl: string | null; watermarkUrl: string }> {
  await ensureDir()
  const ext = extname(filename).toLowerCase()
  const name = `${randomUUID()}${ext}`
  const filePath = join(UPLOAD_DIR, name)
  await writeFile(filePath, buffer)

  let blurUrl: string | null = null

  if (IMAGE_TYPES.includes(ext)) {
    const metadata = await sharp(buffer).metadata()
    const w = metadata.width || 800
    const h = metadata.height || 600
    const fontSize = Math.round(Math.min(w, h) / 8)
    const svg = `<svg width="${w}" height="${h}" xmlns="http://www.w3.org/2000/svg">
      <text x="${w/2}" y="${h*0.5+fontSize*0.15}" text-anchor="middle"
        fill="rgba(255,255,255,0.6)" font-size="${fontSize}px"
        font-family="sans-serif" font-weight="bold"
        transform="rotate(-25 ${w/2} ${h/2})">@${username}</text>
    </svg>`
    const svgBuffer = Buffer.from(svg)

    try {
      const wmBuffer = await sharp(buffer)
        .composite([{ input: svgBuffer, top: 0, left: 0 }])
        .jpeg({ quality: 90 })
        .toBuffer()

      const wmName = `${randomUUID()}-wm.jpg`
      const wmPath = join(UPLOAD_DIR, wmName)
      await writeFile(wmPath, wmBuffer)

      try {
        await sharp(wmBuffer)
          .withMetadata({
            exif: { IFD0: { Artist: `@${username}`, Copyright: `@${username}` } },
          })
          .toFile(wmPath + '_tmp')
        await rename(wmPath + '_tmp', wmPath)
      } catch {}

      const blurName = `${randomUUID()}-blur.jpg`
      const blurPath = join(UPLOAD_DIR, blurName)
      await sharp(wmBuffer).blur(40).jpeg({ quality: 30 }).toFile(blurPath)

      return { url: `/uploads/${name}`, blurUrl: `/uploads/${blurName}`, watermarkUrl: `/uploads/${wmName}` }
    } catch (e) {
      console.error('Watermark failed:', e)
      try {
        const blurName = `${randomUUID()}-blur${ext}`
        const blurPath = join(UPLOAD_DIR, blurName)
        await sharp(buffer).blur(40).jpeg({ quality: 30 }).toFile(blurPath)
        blurUrl = `/uploads/${blurName}`
      } catch {}
    }
  }

  return { url: `/uploads/${name}`, blurUrl, watermarkUrl: url }
}

export async function saveAvatar(buffer: Buffer, filename: string): Promise<string> {
  await ensureDir()
  const ext = extname(filename).toLowerCase()
  const name = `${randomUUID()}${ext}`
  const filePath = join(UPLOAD_DIR, name)
  await sharp(buffer).resize(256, 256, { fit: 'cover' }).toFile(filePath)
  return `/uploads/${name}`
}
