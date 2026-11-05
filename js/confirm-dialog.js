/*
 * Modal de confirmación propio, en lugar del confirm() del navegador.
 *
 * Uso en un formulario:
 *   <form method="post" data-confirm="¿Seguro que deseas eliminar este contacto?"
 *         data-confirm-title="Eliminar contacto" data-confirm-accept="Eliminar" data-confirm-variant="danger">
 *
 * Solo data-confirm es obligatorio. El formulario se envía únicamente si se pulsa el botón de aceptar.
 * También queda disponible window.rcnConfirm({ title, message, accept, variant }), que devuelve una promesa.
 */
(() => {
    if (window.rcnConfirm) return;

    let dialog = null;
    let resolveCurrent = null;

    const build = () => {
        dialog = document.createElement('dialog');
        dialog.className = 'confirm-dialog';
        dialog.setAttribute('aria-labelledby', 'confirm-dialog-title');
        dialog.setAttribute('aria-describedby', 'confirm-dialog-message');
        dialog.innerHTML = '<h2 class="confirm-dialog__title" id="confirm-dialog-title"></h2>'
            + '<p class="confirm-dialog__message" id="confirm-dialog-message"></p>'
            + '<div class="confirm-dialog__actions">'
            + '<button type="button" class="btn btn--secondary" data-confirm-cancel>Cancelar</button>'
            + '<button type="button" class="btn" data-confirm-accept></button>'
            + '</div>';
        document.body.appendChild(dialog);

        const finish = (accepted) => {
            if (dialog.open) dialog.close();
            const resolve = resolveCurrent;
            resolveCurrent = null;
            resolve?.(accepted);
        };
        dialog.querySelector('[data-confirm-cancel]').addEventListener('click', () => finish(false));
        dialog.querySelector('[data-confirm-accept]').addEventListener('click', () => finish(true));
        // Escape cierra el modal: cuenta como cancelar.
        dialog.addEventListener('cancel', (event) => { event.preventDefault(); finish(false); });
        // Clic fuera de la tarjeta (en el fondo oscuro) también cancela.
        dialog.addEventListener('click', (event) => { if (event.target === dialog) finish(false); });
    };

    const confirmAction = ({ title = 'Confirmar acción', message = '', accept = 'Aceptar', variant = '' } = {}) => {
        if (!dialog) build();
        resolveCurrent?.(false);
        dialog.querySelector('.confirm-dialog__title').textContent = title;
        dialog.querySelector('.confirm-dialog__message').textContent = message;
        const acceptButton = dialog.querySelector('[data-confirm-accept]');
        acceptButton.textContent = accept;
        acceptButton.classList.toggle('btn--danger', variant === 'danger');
        dialog.showModal();
        // En acciones destructivas el foco queda en Cancelar, para que Enter no elimine por accidente.
        (variant === 'danger' ? dialog.querySelector('[data-confirm-cancel]') : acceptButton).focus();

        return new Promise((resolve) => { resolveCurrent = resolve; });
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        const submitter = event.submitter && event.submitter.form === form ? event.submitter : null;
        confirmAction({
            title: form.dataset.confirmTitle,
            message: form.dataset.confirm,
            accept: form.dataset.confirmAccept,
            variant: form.dataset.confirmVariant,
        }).then((accepted) => {
            if (!accepted) return;
            form.dataset.confirmed = 'true';
            if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter);
            else form.submit();
        });
    }, true);

    window.rcnConfirm = confirmAction;
})();
