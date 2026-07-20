import { pgTable, text, integer, timestamp, boolean, uniqueIndex, jsonb } from 'drizzle-orm/pg-core'

export const users = pgTable('users', {
  id: text('id').primaryKey(),
  email: text('email').notNull().unique(),
  username: text('username').notNull().unique(),
  displayName: text('display_name').notNull(),
  passwordHash: text('password_hash'),
  avatarUrl: text('avatar_url'),
  bannerUrl: text('banner_url'),
  bio: text('bio').default(''),
  settings: text('settings').default('{}'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
})

export const accounts = pgTable('accounts', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  provider: text('provider').notNull(),
  providerAccountId: text('provider_account_id').notNull(),
  providerRefreshToken: text('provider_refresh_token'),
  providerAccessToken: text('provider_access_token'),
  providerTokenExpiresAt: timestamp('provider_token_expires_at'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const sessions = pgTable('sessions', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  token: text('token').notNull().unique(),
  expiresAt: timestamp('expires_at').notNull(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const posts = pgTable('posts', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  content: text('content').notNull(),
  imageUrl: text('image_url'),
  visibility: text('visibility').default('public'),
  visibleTo: text('visible_to').default('[]'),
  likeCount: integer('like_count').default(0),
  repostCount: integer('repost_count').default(0),
  viewCount: integer('view_count').default(0),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
})

export const likes = pgTable('likes', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  postId: text('post_id').notNull().references(() => posts.id, { onDelete: 'cascade' }),
  createdAt: timestamp('created_at').notNull().defaultNow(),
}, (t) => ({
  userPostIdx: uniqueIndex('likes_user_post_idx').on(t.userId, t.postId),
}))

export const reposts = pgTable('reposts', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  postId: text('post_id').notNull().references(() => posts.id, { onDelete: 'cascade' }),
  createdAt: timestamp('created_at').notNull().defaultNow(),
}, (t) => ({
  userPostIdx: uniqueIndex('reposts_user_post_idx').on(t.userId, t.postId),
}))

export const follows = pgTable('follows', {
  id: text('id').primaryKey(),
  followerId: text('follower_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  followingId: text('following_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  createdAt: timestamp('created_at').notNull().defaultNow(),
}, (t) => ({
  followerFollowingIdx: uniqueIndex('follows_follower_following_idx').on(t.followerId, t.followingId),
}))

export const servers = pgTable('servers', {
  id: text('id').primaryKey(),
  name: text('name').notNull(),
  description: text('description').default(''),
  iconUrl: text('icon_url'),
  bannerUrl: text('banner_url'),
  ownerId: text('owner_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  isPublic: boolean('is_public').default(true),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
})

export const serverRoles = pgTable('server_roles', {
  id: text('id').primaryKey(),
  serverId: text('server_id').notNull().references(() => servers.id, { onDelete: 'cascade' }),
  name: text('name').notNull(),
  color: text('color').default('#99aab5'),
  position: integer('position').default(0),
  permissions: text('permissions').default(''),
  isAdmin: boolean('is_admin').default(false),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const serverMembers = pgTable('server_members', {
  id: text('id').primaryKey(),
  serverId: text('server_id').notNull().references(() => servers.id, { onDelete: 'cascade' }),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  roleId: text('role_id').references(() => serverRoles.id),
  nickname: text('nickname'),
  joinedAt: timestamp('joined_at').notNull().defaultNow(),
}, (t) => ({
  serverUserIdx: uniqueIndex('server_members_server_user_idx').on(t.serverId, t.userId),
}))

export const serverChannels = pgTable('server_channels', {
  id: text('id').primaryKey(),
  serverId: text('server_id').notNull().references(() => servers.id, { onDelete: 'cascade' }),
  name: text('name').notNull(),
  type: text('type').default('text'),
  position: integer('position').default(0),
  description: text('description').default(''),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const channelMessages = pgTable('channel_messages', {
  id: text('id').primaryKey(),
  channelId: text('channel_id').notNull().references(() => serverChannels.id, { onDelete: 'cascade' }),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  content: text('content').notNull(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
})

export const closeFriends = pgTable('close_friends', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  friendId: text('friend_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  createdAt: timestamp('created_at').notNull().defaultNow(),
}, (t) => ({
  userFriendIdx: uniqueIndex('close_friends_user_friend_idx').on(t.userId, t.friendId),
}))

export const friends = pgTable('friends', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  friendId: text('friend_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  status: text('status').notNull().default('pending'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
}, (t) => ({
  userFriendIdx: uniqueIndex('friends_user_friend_idx').on(t.userId, t.friendId),
}))

export const dmChannels = pgTable('dm_channels', {
  id: text('id').primaryKey(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
})

export const dmChannelMembers = pgTable('dm_channel_members', {
  id: text('id').primaryKey(),
  channelId: text('channel_id').notNull().references(() => dmChannels.id, { onDelete: 'cascade' }),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  joinedAt: timestamp('joined_at').notNull().defaultNow(),
}, (t) => ({
  channelUserIdx: uniqueIndex('dm_channel_members_channel_user_idx').on(t.channelId, t.userId),
}))

export const dmMessages = pgTable('dm_messages', {
  id: text('id').primaryKey(),
  channelId: text('channel_id').notNull().references(() => dmChannels.id, { onDelete: 'cascade' }),
  senderId: text('sender_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  content: text('content').notNull(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const serverInvites = pgTable('server_invites', {
  id: text('id').primaryKey(),
  serverId: text('server_id').notNull().references(() => servers.id, { onDelete: 'cascade' }),
  code: text('code').notNull().unique(),
  createdBy: text('created_by').notNull().references(() => users.id),
  maxUses: integer('max_uses').default(0),
  useCount: integer('use_count').default(0),
  expiresAt: timestamp('expires_at'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const postAttachments = pgTable('post_attachments', {
  id: text('id').primaryKey(),
  postId: text('post_id').notNull().references(() => posts.id, { onDelete: 'cascade' }),
  url: text('url').notNull(),
  blurUrl: text('blur_url'),
  watermarkUrl: text('watermark_url'),
  type: text('type').notNull().default('image'),
  mime: text('mime').notNull().default('image/png'),
  position: integer('position').default(0),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})

export const bookmarks = pgTable('bookmarks', {
  id: text('id').primaryKey(),
  userId: text('user_id').notNull().references(() => users.id, { onDelete: 'cascade' }),
  postId: text('post_id').notNull().references(() => posts.id, { onDelete: 'cascade' }),
  createdAt: timestamp('created_at').notNull().defaultNow(),
}, (t) => ({
  userPostIdx: uniqueIndex('bookmarks_user_post_idx').on(t.userId, t.postId),
}))

export const postViews = pgTable('post_views', {
  id: text('id').primaryKey(),
  postId: text('post_id').notNull().references(() => posts.id, { onDelete: 'cascade' }),
  userId: text('user_id'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
})
