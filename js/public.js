const initializePublicPage = () => {
    const button = document.getElementById('btn-top');

    if (button) {
        const updateTopButton = () => button.classList.toggle('show', window.scrollY > 300);
        window.addEventListener('scroll', updateTopButton, { passive: true });
        updateTopButton();
        button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        const delay = Number.parseInt(element.dataset.autoDismiss || '', 10);
        window.setTimeout(() => {
            element.classList.add('alert--dismissing');
            window.setTimeout(() => {
                element.classList.add('alert--dismissed');
                element.setAttribute('hidden', 'hidden');
            }, 320);
        }, Number.isFinite(delay) && delay > 0 ? delay : 5000);
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        }), { threshold: 0.1 });

        document.querySelectorAll('.card').forEach((card) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePublicPage, { once: true });
} else {
    initializePublicPage();
}
