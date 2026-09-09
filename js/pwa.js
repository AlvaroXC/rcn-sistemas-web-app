document.addEventListener('DOMContentLoaded', () => {
    if (!('serviceWorker' in navigator)) return;
    const basePath = document.body.dataset.appBasePath || '';
    navigator.serviceWorker.register(`${basePath}/firebase-messaging-sw.js`, {scope: `${basePath}/`}).catch(() => {});
});
