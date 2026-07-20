import { db } from '../../../db'
import * as schema from '../../../db/schema'
import { eq, desc, inArray, and } from 'drizzle-orm'
import { getCurrentUser } from '../../../utils/auth'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const query = getQuery(event)
  const limit = Math.min(Number(query.limit) || 50, 100)
  const offset = Number(query.offset) || 0

  const currentUser = await getCurrentUser(event)

  const posts = await db.query.posts.findMany({
    where: eq(schema.posts.userId, id),
    limit, offset,
    orderBy: [desc(schema.posts.createdAt)],
  })

  const userIds = [...new Set(posts.map(p => p.userId))]
  const users = userIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, userIds) })
    : []
  const userMap = Object.fromEntries(users.map(u => [u.id, u]))

  const postIds = posts.map(p => p.id)
  const attachments = postIds.length
    ? await db.query.postAttachments.findMany({
        where: inArray(schema.postAttachments.postId, postIds),
        orderBy: [schema.postAttachments.position],
      })
    : []
  const attachMap: Record<string, any[]> = {}
  for (const a of attachments) {
    if (!attachMap[a.postId]) attachMap[a.postId] = []
    attachMap[a.postId].push(a)
  }

  let userLikes = new Set<string>()
  let userReposts = new Set<string>()
  let userBookmarks = new Set<string>()
  if (currentUser && postIds.length) {
    const likes = await db.query.likes.findMany({
      where: and(eq(schema.likes.userId, currentUser.id), inArray(schema.likes.postId, postIds)),
    })
    likes.forEach(l => userLikes.add(l.postId))
    const repsts = await db.query.reposts.findMany({
      where: and(eq(schema.reposts.userId, currentUser.id), inArray(schema.reposts.postId, postIds)),
    })
    repsts.forEach(r => userReposts.add(r.postId))
    const bms = await db.query.bookmarks.findMany({
      where: and(eq(schema.bookmarks.userId, currentUser.id), inArray(schema.bookmarks.postId, postIds)),
    })
    bms.forEach(b => userBookmarks.add(b.postId))
  }

  const result = posts.map(p => ({
    ...p,
    likeCount: p.likeCount ?? 0,
    repostCount: p.repostCount ?? 0,
    user: userMap[p.userId] || null,
    attachments: attachMap[p.id] || [],
    liked: userLikes.has(p.id),
    reposted: userReposts.has(p.id),
    bookmarked: userBookmarks.has(p.id),
  }))

  return { posts: result }
})
