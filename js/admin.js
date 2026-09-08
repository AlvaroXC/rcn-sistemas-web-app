document.addEventListener('DOMContentLoaded', () => {
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
});
