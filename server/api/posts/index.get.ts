import { db } from '../../db'
import * as schema from '../../db/schema'
import { desc, inArray, eq } from 'drizzle-orm'
import { getCurrentUser } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const limit = Math.min(Number(query.limit) || 50, 100)
  const offset = Number(query.offset) || 0
  const timeline = String(query.timeline || 'global')

  let userIds: string[] | undefined
  let orderBy: any = [desc(schema.posts.createdAt)]

  if (timeline !== 'global') {
    const user = await getCurrentUser(event)
    if (user) {
      if (timeline === 'local') {
        const close = await db.query.closeFriends.findMany({
          where: eq(schema.closeFriends.userId, user.id),
          columns: { friendId: true },
        })
        userIds = [user.id, ...close.map(c => c.friendId)]
      } else if (timeline === 'following') {
        const follows = await db.query.follows.findMany({
          where: eq(schema.follows.followerId, user.id),
          columns: { followingId: true },
        })
        userIds = [user.id, ...follows.map(f => f.followingId)]
      } else if (timeline === 'recommended') {
        const follows = await db.query.follows.findMany({
          where: eq(schema.follows.followerId, user.id),
          columns: { followingId: true },
        })
        const followedIds = follows.map(f => f.followingId)
        const likedPosts = await db.query.likes.findMany({
          where: eq(schema.likes.userId, user.id),
          columns: { postId: true },
          limit: 50,
          orderBy: [desc(schema.likes.createdAt)],
        })
        let recommendedIds: string[] = [user.id, ...followedIds]
        if (likedPosts.length) {
          const likedPostIds = likedPosts.map(l => l.postId)
          const likedPosters = await db.query.posts.findMany({
            where: inArray(schema.posts.id, likedPostIds),
            columns: { userId: true },
          })
          recommendedIds = [...new Set([...recommendedIds, ...likedPosters.map(p => p.userId)])]
        }
        userIds = recommendedIds
        orderBy = [desc(schema.posts.likeCount), desc(schema.posts.createdAt)]
      } else if (timeline === 'trending') {
        orderBy = [desc(schema.posts.likeCount), desc(schema.posts.repostCount), desc(schema.posts.createdAt)]
      }
    }
  }

  const where = userIds ? inArray(schema.posts.userId, userIds) : undefined

  const posts = await db.query.posts.findMany({
    limit, offset, where, orderBy,
  })

  const postUserIds = [...new Set(posts.map(p => p.userId))]
  const postUsers = postUserIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, postUserIds) })
    : []
  const postUserMap = Object.fromEntries(postUsers.map(u => [u.id, u]))

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

  const postsWithMeta = posts.map(p => ({
    ...p, user: postUserMap[p.userId] || null,
    attachments: attachMap[p.id] || [],
  }))

  return { posts: postsWithMeta }
})
