self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : { title: 'SYCS Notification', body: 'New Message' };

    const options = {
        body: data.body,
        icon: data.icon || 'assets/img/SYCS_favicon.svg',
        badge: 'assets/img/SYCS_favicon.svg',
        data: data.data
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const url = event.notification.data.url || 'index.php';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(windowClients => {
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
