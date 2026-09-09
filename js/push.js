document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-push-enable]');
    const status = document.querySelector('[data-push-status]');
    if (!button || !('Notification' in window) || !('serviceWorker' in navigator)) return;

    const setStatus = (message, error = false) => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('alert--error', error);
    };

    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const config = JSON.parse(document.body.dataset.firebaseWebConfig || '{}');
            const vapidKey = document.body.dataset.firebaseVapidKey || '';
            if (!config.projectId || !vapidKey) throw new Error('Las notificaciones aún no están configuradas.');
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('Permiso de notificaciones no concedido.');
            const [{ initializeApp }, { getMessaging, getToken, onMessage }] = await Promise.all([
                import('https://www.gstatic.com/firebasejs/12.1.0/firebase-app.js'),
                import('https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging.js'),
            ]);
            const app = initializeApp(config);
            const messaging = getMessaging(app);
            const registration = await navigator.serviceWorker.ready;
            const token = await getToken(messaging, { vapidKey, serviceWorkerRegistration: registration });
            if (!token) throw new Error('Firebase no generó un dispositivo para este navegador.');
            onMessage(messaging, payload => {
                const data = payload.data || {};
                const notification = new Notification(payload.notification?.title || 'RCN Sistemas', {
                    body: payload.notification?.body || 'Tienes una actualización de ticket.',
                    icon: `${window.location.origin}${document.body.dataset.appBasePath || ''}/assets/images/pwa-icon.svg`,
                    data,
                });
                notification.onclick = () => {
                    window.focus();
                    if (data.url) window.location.href = data.url;
                };
            });
            const form = new URLSearchParams({ _csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '', token, platform: /iPhone|iPad|iPod/i.test(navigator.userAgent) ? 'ios-web' : 'web', device_key: `${navigator.platform}:${screen.width}x${screen.height}` });
            const response = await fetch(document.body.dataset.pushRegisterUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'}, body: form});
            if (!response.ok) throw new Error('No se pudo registrar este dispositivo.');
            setStatus('Notificaciones activadas en este dispositivo.');
            button.hidden = true;
        } catch (error) {
            setStatus(error.message || 'No se pudieron activar las notificaciones.', true);
            button.disabled = false;
        }
    });
});
