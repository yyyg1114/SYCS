import { db } from '../db'
import * as schema from '../db/schema'
import { desc, inArray, eq } from 'drizzle-orm'
import { requireAuth } from '../utils/auth'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)

  const bookmarks = await db.query.bookmarks.findMany({
    where: eq(schema.bookmarks.userId, user.id),
    orderBy: [desc(schema.bookmarks.createdAt)],
    limit: 100,
  })

  const postIds = bookmarks.map(b => b.postId)
  if (!postIds.length) return { posts: [] }

  const posts = await db.query.posts.findMany({
    where: inArray(schema.posts.id, postIds),
  })

  const postUserIds = [...new Set(posts.map(p => p.userId))]
  const postUsers = postUserIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, postUserIds) })
    : []
  const postUserMap = Object.fromEntries(postUsers.map(u => [u.id, u]))

  const result = posts.map(p => ({
    ...p, user: postUserMap[p.userId] || null,
    attachments: [], liked: false, reposted: false, bookmarked: true,
  }))

  return { posts: result }
})
