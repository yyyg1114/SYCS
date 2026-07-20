import { SignJWT, jwtVerify } from 'jose'
import { db } from '../db'
import * as schema from '../db/schema'
import { eq } from 'drizzle-orm'
import { randomUUID } from 'crypto'
import { setCookie, getCookie, deleteCookie } from 'h3'
import bcrypt from 'bcryptjs'

const JWT_SECRET = new TextEncoder().encode(process.env.JWT_SECRET || 'sycs-dev-secret-change-in-production-please')
const SESSION_DURATION_MS = 7 * 24 * 60 * 60 * 1000

export interface JwtPayload {
  userId: string
  sessionId: string
}

export async function createToken(payload: JwtPayload): Promise<string> {
  return new SignJWT(payload as unknown as Record<string, unknown>)
    .setProtectedHeader({ alg: 'HS256' })
    .setExpirationTime('7d')
    .setIssuedAt()
    .sign(JWT_SECRET)
}

export async function verifyToken(token: string): Promise<JwtPayload | null> {
  try {
    const { payload } = await jwtVerify(token, JWT_SECRET)
    return payload as unknown as JwtPayload
  } catch { return null }
}

export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, 10)
}

export async function verifyPassword(password: string, hash: string): Promise<boolean> {
  return bcrypt.compare(password, hash)
}

export async function createSession(userId: string, rememberMe = false): Promise<{ token: string }> {
  const sessionId = randomUUID()
  const token = await createToken({ userId, sessionId })
  return { token }
}

export function setAuthCookie(event: any, token: string, rememberMe = false) {
  setCookie(event, 'sycs_token', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: rememberMe ? 30 * 24 * 60 * 60 : SESSION_DURATION_MS / 1000,
  })
}

export function clearAuthCookie(event: any) {
  deleteCookie(event, 'sycs_token')
}

export async function getCurrentUser(event: any) {
  const token = getCookie(event, 'sycs_token')
  if (!token) return null
  const payload = await verifyToken(token)
  if (!payload) return null
  return db.query.users.findFirst({ where: eq(schema.users.id, payload.userId) }) || null
}

export async function requireAuth(event: any) {
  const user = await getCurrentUser(event)
  if (!user) throw createError({ statusCode: 401, message: '認証が必要です' })
  return user
}
