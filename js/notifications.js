document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const streamUrl = body.dataset.notificationStreamUrl || '';
    const badge = document.querySelector('[data-notification-badge]');
    let unreadCount = Number.parseInt(body.dataset.notificationUnreadCount || '0', 10) || 0;

    const syncBadge = (count) => {
        unreadCount = Math.max(0, Number.parseInt(count, 10) || 0);
        if (!badge) {
            return;
        }
        badge.hidden = unreadCount === 0;
        badge.textContent = unreadCount > 0 ? String(unreadCount) : '';
    };

    const showToast = (title, message, warning = false) => {
        const toast = document.createElement('article');
        toast.className = `live-toast live-toast--${warning ? 'warning' : 'assignment'}`;
        toast.innerHTML = `<strong class="live-toast__title"></strong><p class="live-toast__message"></p>`;
        toast.querySelector('.live-toast__title').textContent = title;
        toast.querySelector('.live-toast__message').textContent = message;
        const stack = document.querySelector('[data-live-toast-stack]') || document.body.appendChild(Object.assign(document.createElement('div'), { className: 'live-toast-stack', dataset: { liveToastStack: 'true' } }));
        stack.prepend(toast);
        window.setTimeout(() => toast.remove(), warning ? 10000 : 7000);
    };

    if (!streamUrl || !('EventSource' in window)) {
        syncBadge(unreadCount);
        return;
    }

    const source = new EventSource(streamUrl);
    source.addEventListener('ticket_assignment', (event) => {
        let payload;
        try { payload = JSON.parse(event.data); } catch (error) { return; }
        syncBadge(payload.unread_count ?? unreadCount + 1);
        showToast(payload.title || 'Nueva asignación', payload.message || '');
        const audioUrl = body.dataset.notificationSoundUrl || '';
        if (audioUrl) {
            const audio = new Audio(audioUrl);
            audio.play().catch(() => {});
        }
    });
    source.addEventListener('session_expired', () => {
        source.close();
        showToast('Sesión expirada', 'Tu sesión del panel venció. Vuelve a iniciar sesión.', true);
    });
    window.addEventListener('beforeunload', () => source.close(), { once: true });
    syncBadge(unreadCount);
});
