const initializeAdminEnhancements = () => {
    const layout = document.querySelector('.admin-layout');
    const desktopToggle = document.querySelector('[data-sidebar-toggle]');
    if (layout && desktopToggle) {
        const storageKey = 'rcn-admin-sidebar-collapsed';
        const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;
        const applySidebarState = (collapsed) => {
            layout.classList.toggle('admin-layout--collapsed', collapsed);
            desktopToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            desktopToggle.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
            desktopToggle.title = collapsed ? 'Expandir menú' : 'Contraer menú';
        };
        const savedState = window.localStorage.getItem(storageKey) === 'true';
        applySidebarState(isDesktop() && savedState);
        desktopToggle.addEventListener('click', () => {
            if (!isDesktop()) return;
            const collapsed = !layout.classList.contains('admin-layout--collapsed');
            window.localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            applySidebarState(collapsed);
        });
    }

    const mobileToggle = document.querySelector('[data-mobile-menu-toggle]');
    const mobileClose = document.querySelector('[data-sidebar-close]');
    const mobileSidebar = document.querySelector('[data-mobile-sidebar]');
    const mobileOverlay = document.querySelector('[data-sidebar-overlay]');
    if (!mobileToggle || !mobileClose || !mobileSidebar || !mobileOverlay) return;

    const isMobile = () => window.matchMedia('(max-width: 991px)').matches;
    const focusableElements = () => [...mobileSidebar.querySelectorAll('a[href], button:not([disabled])')];
    const setMobileMenu = (open, restoreFocus = true) => {
        const active = open && isMobile();
        document.body.classList.toggle('mobile-menu-open', active);
        mobileSidebar.classList.toggle('sidebar--mobile-open', active);
        mobileOverlay.hidden = !active;
        mobileToggle.setAttribute('aria-expanded', active ? 'true' : 'false');
        mobileToggle.setAttribute('aria-label', active ? 'Cerrar menú' : 'Abrir menú');
        if (active) window.requestAnimationFrame(() => mobileClose.focus());
        else if (restoreFocus && isMobile()) mobileToggle.focus();
    };

    mobileToggle.addEventListener('click', () => setMobileMenu(true));
    mobileClose.addEventListener('click', () => setMobileMenu(false));
    mobileOverlay.addEventListener('click', () => setMobileMenu(false));
    mobileSidebar.querySelectorAll('.sidebar__link').forEach((link) => link.addEventListener('click', () => {
        if (isMobile()) setMobileMenu(false, false);
    }));
    document.addEventListener('keydown', (event) => {
        if (!document.body.classList.contains('mobile-menu-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setMobileMenu(false);
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = focusableElements();
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    window.addEventListener('resize', () => {
        if (!isMobile()) setMobileMenu(false, false);
    });

    const startInput = document.getElementById('service_start_time');
    const endInput = document.getElementById('service_end_time');
    const durationInput = document.getElementById('service_duration_preview');

    const updateDuration = () => {
        if (!startInput || !endInput || !durationInput) {
            return;
        }

        const start = startInput.value.split(':').map(Number);
        const end = endInput.value.split(':').map(Number);
        const valid = start.length === 2 && end.length === 2 && start.every(Number.isFinite) && end.every(Number.isFinite);
        const totalStart = valid ? (start[0] * 60) + start[1] : 0;
        const totalEnd = valid ? (end[0] * 60) + end[1] : 0;
        const minutes = totalEnd >= totalStart ? totalEnd - totalStart : -1;

        if (minutes < 0) {
            durationInput.value = 'Pendiente de registrar';
            return;
        }

        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        durationInput.value = hours > 0 && remainingMinutes > 0
            ? `${hours} h ${remainingMinutes} min`
            : hours > 0 ? `${hours} h` : `${remainingMinutes} min`;
    };

    [startInput, endInput].forEach((input) => input?.addEventListener('input', updateDuration));
    [startInput, endInput].forEach((input) => input?.addEventListener('change', updateDuration));
    updateDuration();
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAdminEnhancements, { once: true });
else initializeAdminEnhancements();
