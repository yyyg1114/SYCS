import { requireAuth } from '../utils/auth'
import { on } from '../utils/eventBus'

export default defineEventHandler(async (event) => {
  const user = await requireAuth(event)

  setResponseHeader(event, 'Content-Type', 'text/event-stream')
  setResponseHeader(event, 'Cache-Control', 'no-cache')
  setResponseHeader(event, 'Connection', 'keep-alive')
  setResponseHeader(event, 'X-Accel-Buffering', 'no')

  const res = event.node.res
  res.writeHead(200, {
    'Content-Type': 'text/event-stream',
    'Cache-Control': 'no-cache',
    'Connection': 'keep-alive',
    'X-Accel-Buffering': 'no',
  })

  const send = (data: any) => {
    try { res.write(`data: ${JSON.stringify(data)}\n\n`) } catch {}
  }

  const unsubs = [
    on('post:created', data => send(data)),
    on('post:liked', data => send(data)),
    on('post:reposted', data => send(data)),
  ]

  const keepAlive = setInterval(() => send({ type: 'ping' }), 15000)

  event.node.req.on('close', () => {
    unsubs.forEach(fn => fn())
    clearInterval(keepAlive)
  })
})
