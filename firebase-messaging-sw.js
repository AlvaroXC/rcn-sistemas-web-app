importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging-compat.js');

fetch(new URL('firebase-config.json', self.registration.scope))
    .then(response => response.json())
    .then(config => {
        if (!config.projectId) return;
        firebase.initializeApp(config);
        firebase.messaging().onBackgroundMessage(payload => {
            const data = payload.data || {};
            self.registration.showNotification(payload.notification?.title || 'RCN Sistemas', {
                body: payload.notification?.body || 'Tienes una actualización de ticket.',
                icon: new URL('assets/images/pwa-icon.svg', self.registration.scope).href,
                data: {url: data.url || new URL('admin/notificaciones', self.registration.scope).href}
            });
        });
    })
    .catch(() => {});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = event.notification.data?.url || new URL('admin/notificaciones', self.registration.scope).href;
    event.waitUntil(self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(clients => {
        for (const client of clients) {
            if ('focus' in client) {
                client.navigate(target);
                return client.focus();
            }
        }
        return self.clients.openWindow(target);
    }));
});
