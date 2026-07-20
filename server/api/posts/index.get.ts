import { db } from '../../db'
import * as schema from '../../db/schema'
import { desc, inArray, eq, and, or } from 'drizzle-orm'
import { getCurrentUser } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const limit = Math.min(Number(query.limit) || 50, 100)
  const offset = Number(query.offset) || 0
  const timeline = String(query.timeline || 'global')

  const currentUser = await getCurrentUser(event)

  let userIds: string[] | undefined
  let orderBy: any = [desc(schema.posts.createdAt)]

  if (timeline !== 'global') {
    if (currentUser) {
      if (timeline === 'local') {
        const close = await db.query.closeFriends.findMany({
          where: eq(schema.closeFriends.userId, currentUser.id),
          columns: { friendId: true },
        })
        userIds = [currentUser.id, ...close.map(c => c.friendId)]
      } else if (timeline === 'following') {
        const follows = await db.query.follows.findMany({
          where: eq(schema.follows.followerId, currentUser.id),
          columns: { followingId: true },
        })
        userIds = [currentUser.id, ...follows.map(f => f.followingId)]
      } else if (timeline === 'recommended') {
        const follows = await db.query.follows.findMany({
          where: eq(schema.follows.followerId, currentUser.id),
          columns: { followingId: true },
        })
        const followedIds = follows.map(f => f.followingId)
        const likedPosts = await db.query.likes.findMany({
          where: eq(schema.likes.userId, currentUser.id),
          columns: { postId: true },
          limit: 50,
          orderBy: [desc(schema.likes.createdAt)],
        })
        let recommendedIds: string[] = [currentUser.id, ...followedIds]
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

  const rawPosts = await db.query.posts.findMany({
    limit, offset, where, orderBy,
  })

  // Visibility filtering
  let followers = new Set<string>()
  let closeFriends = new Set<string>()
  if (currentUser) {
    const fls = await db.query.follows.findMany({
      where: eq(schema.follows.followerId, currentUser.id),
    })
    fls.forEach(f => followers.add(f.followingId))
    const cfs = await db.query.closeFriends.findMany({
      where: eq(schema.closeFriends.userId, currentUser.id),
    })
    cfs.forEach(f => closeFriends.add(f.friendId))
  }

  const posts = rawPosts.filter(p => {
    if (p.visibility === 'public') return true
    if (!currentUser) return false
    if (p.userId === currentUser.id) return true
    if (p.visibility === 'followers' && followers.has(p.userId)) return true
    if (p.visibility === 'close_friends' && closeFriends.has(p.userId)) return true
    if (p.visibility === 'specific') {
      const visibleTo = JSON.parse(p.visibleTo || '[]')
      if (visibleTo.includes(currentUser.id)) return true
    }
    return false
  })

  const postUserIds = [...new Set(posts.map(p => p.userId))]
  const postUsers = postUserIds.length
    ? await db.query.users.findMany({ where: inArray(schema.users.id, postUserIds) })
    : []
  const postUserMap = Object.fromEntries(postUsers.map(u => [u.id, u]))

  const postIds = posts.map(p => p.id)

  // Attachments
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

  // User interaction status
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
    user: postUserMap[p.userId] || null,
    attachments: attachMap[p.id] || [],
    liked: userLikes.has(p.id),
    reposted: userReposts.has(p.id),
    bookmarked: userBookmarks.has(p.id),
  }))

  return { posts: result }
})
