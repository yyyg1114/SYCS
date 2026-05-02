/**
 * SYCS Socket Module
 */

export let socket = null;

export function initSocket(userId, callbacks) {
  if (typeof io === 'undefined') return;
  
  socket = io('http://localhost:3000', {
    query: { userId }
  });

  socket.on('connect', () => {
    console.log('Connected to realtime server');
    if (callbacks.onConnect) callbacks.onConnect();
  });

  socket.on('new_message', (data) => {
    if (callbacks.onNewMessage) callbacks.onNewMessage(data);
  });

  socket.on('new_group_message', (data) => {
    if (callbacks.onNewGroupMessage) callbacks.onNewGroupMessage(data);
  });

  socket.on('new_dm', (data) => {
    if (callbacks.onNewDm) callbacks.onNewDm(data);
  });

  socket.on('user_status_change', (data) => {
    if (callbacks.onStatusChange) callbacks.onStatusChange(data);
  });

  socket.on('typing', (data) => {
    if (callbacks.onTyping) callbacks.onTyping(data);
  });

  return socket;
}
