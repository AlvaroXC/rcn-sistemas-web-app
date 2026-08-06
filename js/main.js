document.addEventListener('DOMContentLoaded', () => {
    const btnTop = document.getElementById('btn-top');

    if (btnTop) {
        // Show/Hide scroll-to-top button
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btnTop.classList.add('show');
            } else {
                btnTop.classList.remove('show');
            }
        });

        // Scroll to top
        btnTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    document.querySelectorAll('[data-auto-dismiss]').forEach((alertElement) => {
        const dismissDelay = Number.parseInt(alertElement.dataset.autoDismiss || '', 10);
        const timeout = Number.isFinite(dismissDelay) && dismissDelay > 0 ? dismissDelay : 5000;

        window.setTimeout(() => {
            alertElement.classList.add('alert--dismissing');

            window.setTimeout(() => {
                alertElement.classList.add('alert--dismissed');
                alertElement.setAttribute('hidden', 'hidden');
            }, 320);
        }, timeout);
    });

    // Simple fade-in animation for cards on scroll
    const observerOptions = {
        threshold: 0.1
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    }
});
