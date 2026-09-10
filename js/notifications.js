const initializeNotifications = () => {
    const body = document.body;
    const streamUrl = body.dataset.notificationStreamUrl || '';
    const badges = [...document.querySelectorAll('[data-notification-badge]')];
    let unreadCount = Number.parseInt(body.dataset.notificationUnreadCount || '0', 10) || 0;

    const syncBadge = (count) => {
        unreadCount = Math.max(0, Number.parseInt(count, 10) || 0);
        if (!badges.length) {
            return;
        }
        badges.forEach((badge) => {
            badge.hidden = unreadCount === 0;
            badge.textContent = unreadCount > 0 ? String(unreadCount) : '';
        });
    };

    const showToast = (title, message, warning = false, url = '') => {
        const toast = document.createElement('article');
        toast.className = `live-toast live-toast--${warning ? 'warning' : 'assignment'}`;
        toast.innerHTML = `<span class="live-toast__icon" aria-hidden="true"><i class="fas fa-${warning ? 'triangle-exclamation' : 'bell'}"></i></span><div class="live-toast__content"><strong class="live-toast__title"></strong><p class="live-toast__message"></p><div class="live-toast__actions"><a class="live-toast__action" href="#">Ver notificaciones <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div></div><button class="live-toast__close" type="button" aria-label="Cerrar notificación"><i class="fas fa-times" aria-hidden="true"></i></button>`;
        toast.querySelector('.live-toast__title').textContent = title;
        toast.querySelector('.live-toast__message').textContent = message;
        toast.querySelector('.live-toast__action').href = url || body.dataset.notificationListUrl || '#';
        toast.querySelector('.live-toast__close').addEventListener('click', () => toast.remove());
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
        showToast(payload.title || 'Nueva asignación', payload.message || '', false, payload.ticket_url || payload.url || '');
        const audioUrl = body.dataset.notificationSoundUrl || '';
        if (audioUrl) {
            const audio = new Audio(audioUrl);
            audio.play().catch(() => {});
        }
    });
    source.addEventListener('session_expired', (event) => {
        source.close();
        let payload = {};
        try { payload = JSON.parse(event.data || '{}'); } catch (error) {}
        showToast('Sesión expirada', 'Tu sesión del panel venció. Vuelve a iniciar sesión.', true, payload.login_url || body.dataset.notificationLoginUrl || '');
    });
    window.addEventListener('beforeunload', () => source.close(), { once: true });
    syncBadge(unreadCount);
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeNotifications, { once: true });
else initializeNotifications();
