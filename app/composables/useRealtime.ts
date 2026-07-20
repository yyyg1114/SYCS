export function useRealtime() {
  const events = ref<any[]>([])
  const es = shallowRef<EventSource | null>(null)

  function connect() {
    if (es.value) return
    try {
      const source = new EventSource('/api/events')
      source.onmessage = (e) => {
        try {
          const data = JSON.parse(e.data)
          if (data.type !== 'ping') events.value.push(data)
        } catch {}
      }
      source.onerror = () => {
        source.close()
        es.value = null
        setTimeout(connect, 5000)
      }
      es.value = source
    } catch {
      setTimeout(connect, 5000)
    }
  }

  function disconnect() {
    es.value?.close()
    es.value = null
  }

  onUnmounted(disconnect)

  return { connect, disconnect, events }
}
