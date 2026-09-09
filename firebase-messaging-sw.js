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
        firebase.messaging().onBackgroundMessage(payload => {
            const data = payload.data || {};
            console.info('[RCN Push] Mensaje recibido en segundo plano.', {type: data.type, ticketId: data.ticket_id});
            self.registration.showNotification(data.title || 'RCN Sistemas', {
                body: data.body || 'Tienes una actualización de ticket.',
                icon: data.icon || new URL('assets/images/pwa-icon.svg', self.registration.scope).href,
                data: {url: data.url || new URL('admin/notificaciones', self.registration.scope).href},
            });
        });
    })
    .catch(error => console.error('No se pudo inicializar Firebase Messaging en el Service Worker.', error));
