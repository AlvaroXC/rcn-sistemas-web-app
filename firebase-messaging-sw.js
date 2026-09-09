self.addEventListener('pushsubscriptionchange', () => {});

self.addEventListener('push', event => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        console.error('No se pudo leer el payload push.', error);
    }

    const data = payload.data || payload.notification || payload;
    const title = data.title || 'RCN Sistemas';
    const options = {
        body: data.body || 'Tienes una actualización de ticket.',
        icon: data.icon || new URL('assets/images/pwa-icon.svg', self.registration.scope).href,
        data: {url: data.url || data.link || ''},
        tag: data.tag || 'rcn-notification',
        renotify: true,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const notificationData = event.notification.data || {};
    const fcmMessage = notificationData.FCM_MSG || {};
    const target = notificationData.url || fcmMessage.data?.url || fcmMessage.fcmOptions?.link || new URL('admin/notificaciones', self.registration.scope).href;
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

importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging-compat.js');

fetch(new URL('firebase-config.json', self.registration.scope))
    .then(response => response.json())
    .then(config => {
        if (!config.projectId) return;
        firebase.initializeApp(config);
        firebase.messaging();
    })
    .catch(error => console.error('No se pudo inicializar Firebase Messaging en el Service Worker.', error));
