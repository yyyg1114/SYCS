import { drizzle } from 'drizzle-orm/node-postgres'
import pg from 'pg'
import * as schema from './schema'

const pool = new pg.Pool({
  connectionString: process.env.DATABASE_URL || 'postgres://sycs:sycs_password@localhost:5432/sycs',
})

let initPromise: Promise<void> | null = null

export async function ensureDb() {
  if (!initPromise) {
    initPromise = initDbInternal()
  }
  return initPromise
}

async function initDbInternal() {
  const client = await pool.connect()
  try {
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        id TEXT PRIMARY KEY,
        email TEXT NOT NULL UNIQUE,
        username TEXT NOT NULL UNIQUE,
        display_name TEXT NOT NULL,
        password_hash TEXT,
        avatar_url TEXT,
        banner_url TEXT,
        bio TEXT DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS accounts (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        provider TEXT NOT NULL,
        provider_account_id TEXT NOT NULL,
        provider_refresh_token TEXT,
        provider_access_token TEXT,
        provider_token_expires_at TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        token TEXT NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS posts (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        content TEXT NOT NULL,
        image_url TEXT,
        like_count INTEGER DEFAULT 0,
        repost_count INTEGER DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS likes (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        post_id TEXT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS likes_user_post_idx ON likes(user_id, post_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS reposts (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        post_id TEXT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS reposts_user_post_idx ON reposts(user_id, post_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS follows (
        id TEXT PRIMARY KEY,
        follower_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS follows_follower_following_idx ON follows(follower_id, following_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS servers (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT DEFAULT '',
        icon_url TEXT,
        banner_url TEXT,
        owner_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS server_roles (
        id TEXT PRIMARY KEY,
        server_id TEXT NOT NULL REFERENCES servers(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        color TEXT DEFAULT '#99aab5',
        position INTEGER DEFAULT 0,
        permissions TEXT DEFAULT '',
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS server_members (
        id TEXT PRIMARY KEY,
        server_id TEXT NOT NULL REFERENCES servers(id) ON DELETE CASCADE,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        role_id TEXT REFERENCES server_roles(id),
        nickname TEXT,
        joined_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS server_members_server_user_idx ON server_members(server_id, user_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS server_channels (
        id TEXT PRIMARY KEY,
        server_id TEXT NOT NULL REFERENCES servers(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'text',
        position INTEGER DEFAULT 0,
        description TEXT DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS channel_messages (
        id TEXT PRIMARY KEY,
        channel_id TEXT NOT NULL REFERENCES server_channels(id) ON DELETE CASCADE,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        content TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS server_invites (
        id TEXT PRIMARY KEY,
        server_id TEXT NOT NULL REFERENCES servers(id) ON DELETE CASCADE,
        code TEXT NOT NULL UNIQUE,
        created_by TEXT NOT NULL REFERENCES users(id),
        max_uses INTEGER DEFAULT 0,
        use_count INTEGER DEFAULT 0,
        expires_at TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS close_friends (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        friend_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS close_friends_user_friend_idx ON close_friends(user_id, friend_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS friends (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        friend_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS friends_user_friend_idx ON friends(user_id, friend_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS dm_channels (
        id TEXT PRIMARY KEY,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS dm_channel_members (
        id TEXT PRIMARY KEY,
        channel_id TEXT NOT NULL REFERENCES dm_channels(id) ON DELETE CASCADE,
        user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        joined_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE UNIQUE INDEX IF NOT EXISTS dm_channel_members_channel_user_idx ON dm_channel_members(channel_id, user_id)
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS dm_messages (
        id TEXT PRIMARY KEY,
        channel_id TEXT NOT NULL REFERENCES dm_channels(id) ON DELETE CASCADE,
        sender_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        content TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
    await client.query(`
      CREATE TABLE IF NOT EXISTS post_attachments (
        id TEXT PRIMARY KEY,
        post_id TEXT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        url TEXT NOT NULL,
        blur_url TEXT,
        watermark_url TEXT,
        type TEXT NOT NULL DEFAULT 'image',
        mime TEXT NOT NULL DEFAULT 'image/png',
        position INTEGER DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `)
  } finally {
    client.release()
  }
}

export const db = drizzle(pool, { schema })

// Kept for backward compatibility - route handlers may still call this,
// but the server plugin handles initialization at startup.
export const initDb = ensureDb
