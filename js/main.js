document.addEventListener('DOMContentLoaded', () => {
    const serviceStartInput = document.getElementById('service_start_time');
    const serviceEndInput = document.getElementById('service_end_time');
    const serviceDurationInput = document.getElementById('service_duration_preview');
    const btnTop = document.getElementById('btn-top');

    const formatServiceDuration = (startValue, endValue) => {
        if (!startValue || !endValue) {
            return 'Pendiente de registrar';
        }

        const [startHours, startMinutes] = startValue.split(':').map(Number);
        const [endHours, endMinutes] = endValue.split(':').map(Number);

        if (
            [startHours, startMinutes, endHours, endMinutes].some((value) => Number.isNaN(value))
            || startHours < 0
            || startHours > 23
            || endHours < 0
            || endHours > 23
            || startMinutes < 0
            || startMinutes > 59
            || endMinutes < 0
            || endMinutes > 59
        ) {
            return 'Pendiente de registrar';
        }

        const startTotalMinutes = (startHours * 60) + startMinutes;
        const endTotalMinutes = (endHours * 60) + endMinutes;

        if (endTotalMinutes < startTotalMinutes) {
            return 'Pendiente de registrar';
        }

        const totalMinutes = endTotalMinutes - startTotalMinutes;
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        if (hours > 0 && minutes > 0) {
            return `${hours} h ${minutes} min`;
        }

        if (hours > 0) {
            return `${hours} h`;
        }

        return `${minutes} min`;
    };

    const syncServiceDurationPreview = () => {
        if (!serviceStartInput || !serviceEndInput || !serviceDurationInput) {
            return;
        }

        serviceDurationInput.value = formatServiceDuration(serviceStartInput.value, serviceEndInput.value);
    };

    if (serviceStartInput && serviceEndInput && serviceDurationInput) {
        serviceStartInput.addEventListener('input', syncServiceDurationPreview);
        serviceEndInput.addEventListener('input', syncServiceDurationPreview);
        serviceStartInput.addEventListener('change', syncServiceDurationPreview);
        serviceEndInput.addEventListener('change', syncServiceDurationPreview);
    }

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
