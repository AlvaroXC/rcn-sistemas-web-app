document.addEventListener('DOMContentLoaded', () => {
    const serviceStartInput = document.getElementById('service_start_time');
    const serviceEndInput = document.getElementById('service_end_time');
    const serviceDurationInput = document.getElementById('service_duration_preview');
    const btnTop = document.getElementById('btn-top');
    const notificationStreamUrl = document.body.dataset.notificationStreamUrl || '';
    const notificationLoginUrl = document.body.dataset.notificationLoginUrl || 'login.php';
    const notificationListUrl = document.body.dataset.notificationListUrl || 'notifications.php';
    const notificationSoundUrl = document.body.dataset.notificationSoundUrl || '';
    const notificationBadge = document.querySelector('[data-notification-badge]');

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

    const parseNotificationCount = (value) => {
        const parsedValue = Number.parseInt(value || '', 10);
        return Number.isFinite(parsedValue) && parsedValue >= 0 ? parsedValue : 0;
    };

    const createNotificationSoundController = () => {
        const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
        const baseAudio = notificationSoundUrl ? new Audio(notificationSoundUrl) : null;
        if (baseAudio) {
            baseAudio.preload = 'auto';
        }

        if (!AudioContextCtor && !baseAudio) {
            return {
                playAssignmentSound: async () => false,
            };
        }

        let audioContext = null;
        let unlockListenersAttached = false;
        let unlockInProgress = false;
        let htmlAudioUnlocked = false;

        const ensureAudioContext = () => {
            if (!audioContext) {
                audioContext = new AudioContextCtor();
            }

            return audioContext;
        };

        const detachUnlockListeners = () => {
            if (!unlockListenersAttached) {
                return;
            }

            ['pointerdown', 'keydown', 'touchstart'].forEach((eventName) => {
                window.removeEventListener(eventName, unlockAudioContext);
            });
            unlockListenersAttached = false;
        };

        const unlockAudioContext = async () => {
            if (unlockInProgress) {
                return false;
            }

            unlockInProgress = true;

            try {
                let contextUnlocked = true;
                if (AudioContextCtor) {
                    const context = ensureAudioContext();
                    if (context.state === 'suspended') {
                        await context.resume();
                    }
                    contextUnlocked = context.state === 'running';
                }

                let audioElementUnlocked = !baseAudio;
                if (baseAudio && !htmlAudioUnlocked) {
                    const previousMuted = baseAudio.muted;
                    const previousVolume = baseAudio.volume;

                    try {
                        baseAudio.muted = true;
                        baseAudio.volume = 0;
                        baseAudio.currentTime = 0;
                        await baseAudio.play();
                        baseAudio.pause();
                        baseAudio.currentTime = 0;
                        htmlAudioUnlocked = true;
                    } catch (error) {
                        htmlAudioUnlocked = false;
                    } finally {
                        baseAudio.muted = previousMuted;
                        baseAudio.volume = previousVolume;
                    }
                    audioElementUnlocked = htmlAudioUnlocked;
                }

                const unlocked = contextUnlocked || audioElementUnlocked;
                if (unlocked) {
                    detachUnlockListeners();
                }

                return unlocked;
            } catch (error) {
                return false;
            } finally {
                unlockInProgress = false;
            }
        };

        const attachUnlockListeners = () => {
            if (unlockListenersAttached) {
                return;
            }

            ['pointerdown', 'keydown', 'touchstart'].forEach((eventName) => {
                window.addEventListener(eventName, unlockAudioContext, { passive: true });
            });
            unlockListenersAttached = true;
        };

        const playAssignmentSound = async () => {
            try {
                if (baseAudio) {
                    if (!htmlAudioUnlocked) {
                        const unlocked = await unlockAudioContext();
                        if (!unlocked && !htmlAudioUnlocked) {
                            attachUnlockListeners();
                        }
                    }

                    if (htmlAudioUnlocked) {
                        const playableAudio = baseAudio.cloneNode();
                        playableAudio.volume = 0.9;
                        playableAudio.currentTime = 0;
                        await playableAudio.play();
                        return true;
                    }
                }

                if (!AudioContextCtor) {
                    return false;
                }

                const context = ensureAudioContext();
                if (context.state !== 'running') {
                    const unlocked = await unlockAudioContext();
                    if (!unlocked) {
                        attachUnlockListeners();
                        return false;
                    }
                }

                const startedAt = context.currentTime;
                const primaryOscillator = context.createOscillator();
                const secondaryOscillator = context.createOscillator();
                const gainNode = context.createGain();

                primaryOscillator.type = 'sine';
                primaryOscillator.frequency.setValueAtTime(880, startedAt);
                primaryOscillator.frequency.exponentialRampToValueAtTime(1046.5, startedAt + 0.2);

                secondaryOscillator.type = 'triangle';
                secondaryOscillator.frequency.setValueAtTime(659.25, startedAt);
                secondaryOscillator.frequency.exponentialRampToValueAtTime(880, startedAt + 0.2);

                gainNode.gain.setValueAtTime(0.0001, startedAt);
                gainNode.gain.exponentialRampToValueAtTime(0.14, startedAt + 0.02);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, startedAt + 0.34);

                primaryOscillator.connect(gainNode);
                secondaryOscillator.connect(gainNode);
                gainNode.connect(context.destination);

                primaryOscillator.start(startedAt);
                secondaryOscillator.start(startedAt);
                primaryOscillator.stop(startedAt + 0.34);
                secondaryOscillator.stop(startedAt + 0.34);

                return true;
            } catch (error) {
                attachUnlockListeners();
                return false;
            }
        };

        attachUnlockListeners();

        return {
            playAssignmentSound,
        };
    };

    let unreadNotificationCount = parseNotificationCount(document.body.dataset.notificationUnreadCount);
    const notificationSoundController = createNotificationSoundController();

    const syncNotificationBadge = (count) => {
        if (!notificationBadge) {
            return;
        }

        unreadNotificationCount = Number.isFinite(count) && count >= 0 ? count : 0;
        document.body.dataset.notificationUnreadCount = String(unreadNotificationCount);

        if (unreadNotificationCount > 0) {
            notificationBadge.hidden = false;
            notificationBadge.textContent = String(unreadNotificationCount);
            return;
        }

        notificationBadge.hidden = true;
        notificationBadge.textContent = '';
    };

    syncNotificationBadge(unreadNotificationCount);

    const ensureToastStack = () => {
        let toastStack = document.querySelector('[data-live-toast-stack]');
        if (toastStack) {
            return toastStack;
        }

        toastStack = document.createElement('div');
        toastStack.className = 'live-toast-stack';
        toastStack.setAttribute('data-live-toast-stack', 'true');
        document.body.appendChild(toastStack);
        return toastStack;
    };

    const removeToast = (toastElement) => {
        if (!toastElement || !toastElement.parentNode) {
            return;
        }

        toastElement.classList.add('live-toast--closing');
        window.setTimeout(() => {
            if (toastElement.parentNode) {
                toastElement.parentNode.removeChild(toastElement);
            }
        }, 220);
    };

    const showLiveToast = ({ title, message, ticketUrl, variant = 'assignment', actionLabel = 'Ver ticket' }) => {
        const toastStack = ensureToastStack();
        const toastElement = document.createElement('article');
        toastElement.className = `live-toast live-toast--${variant}`;

        const iconElement = document.createElement('span');
        iconElement.className = 'live-toast__icon';
        iconElement.setAttribute('aria-hidden', 'true');
        iconElement.innerHTML = variant === 'warning'
            ? '<i class="fas fa-exclamation-circle"></i>'
            : '<i class="fas fa-bell"></i>';

        const contentElement = document.createElement('div');
        contentElement.className = 'live-toast__content';

        const titleElement = document.createElement('h3');
        titleElement.className = 'live-toast__title';
        titleElement.textContent = title || 'Nueva notificacion';

        const messageElement = document.createElement('p');
        messageElement.className = 'live-toast__message';
        messageElement.textContent = message || '';

        const actionsElement = document.createElement('div');
        actionsElement.className = 'live-toast__actions';

        if (ticketUrl) {
            const actionLink = document.createElement('a');
            actionLink.className = 'live-toast__action';
            actionLink.href = ticketUrl;
            actionLink.textContent = actionLabel;
            actionsElement.appendChild(actionLink);
        } else if (variant === 'warning') {
            const actionLink = document.createElement('a');
            actionLink.className = 'live-toast__action';
            actionLink.href = notificationLoginUrl;
            actionLink.textContent = 'Iniciar sesion';
            actionsElement.appendChild(actionLink);
        }

        const secondaryLink = document.createElement('a');
        secondaryLink.className = 'live-toast__secondary-action';
        secondaryLink.href = notificationListUrl;
        secondaryLink.textContent = 'Bandeja';
        actionsElement.appendChild(secondaryLink);

        const closeButton = document.createElement('button');
        closeButton.className = 'live-toast__close';
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Cerrar notificacion');
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        closeButton.addEventListener('click', () => removeToast(toastElement));

        contentElement.appendChild(titleElement);
        contentElement.appendChild(messageElement);
        contentElement.appendChild(actionsElement);

        toastElement.appendChild(iconElement);
        toastElement.appendChild(contentElement);
        toastElement.appendChild(closeButton);
        toastStack.prepend(toastElement);

        window.setTimeout(() => {
            if (toastElement.isConnected) {
                removeToast(toastElement);
            }
        }, variant === 'warning' ? 10000 : 7000);
    };

    if (notificationStreamUrl && 'EventSource' in window) {
        const seenNotificationIds = new Set();
        const rememberNotificationId = (notificationId) => {
            seenNotificationIds.add(notificationId);

            if (seenNotificationIds.size <= 100) {
                return;
            }

            const firstSeenId = seenNotificationIds.values().next().value;
            if (firstSeenId !== undefined) {
                seenNotificationIds.delete(firstSeenId);
            }
        };

        const eventSource = new EventSource(notificationStreamUrl);

        eventSource.addEventListener('ticket_assignment', (event) => {
            let payload;

            try {
                payload = JSON.parse(event.data);
            } catch (error) {
                return;
            }

            const notificationId = Number.parseInt(payload.notification_id || '', 10);
            if (Number.isFinite(notificationId) && seenNotificationIds.has(notificationId)) {
                return;
            }

            if (Number.isFinite(notificationId)) {
                rememberNotificationId(notificationId);
            }

            if (Number.isFinite(Number.parseInt(payload.unread_count, 10))) {
                syncNotificationBadge(Number.parseInt(payload.unread_count, 10));
            } else {
                syncNotificationBadge(unreadNotificationCount + 1);
            }

            showLiveToast({
                title: payload.title || 'Nueva asignacion',
                message: payload.message || '',
                ticketUrl: payload.ticket_url || '',
            });

            void notificationSoundController.playAssignmentSound();
        });

        eventSource.addEventListener('session_expired', (event) => {
            let payload = null;

            try {
                payload = JSON.parse(event.data);
            } catch (error) {
                payload = null;
            }

            eventSource.close();
            showLiveToast({
                title: 'Sesion expirada',
                message: payload && payload.message ? payload.message : 'Tu sesion del panel vencio. Vuelve a iniciar sesion para seguir recibiendo avisos.',
                ticketUrl: '',
                variant: 'warning',
                actionLabel: 'Iniciar sesion',
            });
        });

        window.addEventListener('beforeunload', () => {
            eventSource.close();
        }, { once: true });
    }
});
