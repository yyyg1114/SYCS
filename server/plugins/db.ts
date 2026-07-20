import { ensureDb } from '../db'

export default defineNitroPlugin(async () => {
  console.log('[db] Connecting to database...')
  try {
    await ensureDb()
    console.log('[db] Database initialized successfully')
  } catch (err) {
    console.error('[db] Failed to initialize database:', err)
    throw err
  }
})
