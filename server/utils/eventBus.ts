type Listener = (data: any) => void
const listeners = new Map<string, Set<Listener>>()

export function on(event: string, cb: Listener) {
  if (!listeners.has(event)) listeners.set(event, new Set())
  listeners.get(event)!.add(cb)
  return () => listeners.get(event)?.delete(cb)
}

export function emit(event: string, data: any) {
  listeners.get(event)?.forEach(cb => cb(data))
}
