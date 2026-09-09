(() => {
    if (!('serviceWorker' in navigator)) return;
    const basePath = document.body.dataset.appBasePath || '';
    const version = document.body.dataset.pwaVersion || String(Date.now());
    navigator.serviceWorker.register(`${basePath}/firebase-messaging-sw.js?v=${encodeURIComponent(version)}`, {scope: `${basePath}/`}).catch(error => {
        const status = document.querySelector('[data-push-status]');
        if (status) {
            status.textContent = 'No se pudo preparar el servicio de notificaciones. Recarga la página e inténtalo de nuevo.';
            status.classList.add('alert--error');
            status.setAttribute('role', 'alert');
        }
        console.error('No se pudo registrar el Service Worker de notificaciones.', error);
    });
})();
