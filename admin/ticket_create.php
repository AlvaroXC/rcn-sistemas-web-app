<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$allowedStatuses = ['Nuevo', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];
$allowedTypes = ['Compra', 'Soporte', 'Informacion'];

$customerMode = trim((string) ($_POST['customer_mode'] ?? ($_GET['customer_id'] ?? 0 ? 'existing' : 'new')));
if (!in_array($customerMode, ['existing', 'new'], true)) {
    $customerMode = 'new';
}

$selectedCustomerId = (int) ($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
$customerQuery = trim((string) ($_GET['customer_query'] ?? $_POST['customer_query'] ?? ''));
$selectedCustomer = $selectedCustomerId > 0 ? getCustomerById($conn, $selectedCustomerId) : null;
$customerResults = [];

if ($customerQuery !== '' && mb_strlen($customerQuery) >= 3) {
    $customerOptions = getCustomers($conn, $customerQuery, 5, 0);
    if ($customerOptions && $customerOptions->num_rows > 0) {
        while ($customerOption = $customerOptions->fetch_assoc()) {
            $customerResults[] = $customerOption;
        }
    }
}

if ($selectedCustomer && $selectedCustomerId > 0) {
    $selectedCustomerAlreadyListed = false;

    foreach ($customerResults as $customerResult) {
        if ((int) $customerResult['id'] === $selectedCustomerId) {
            $selectedCustomerAlreadyListed = true;
            break;
        }
    }

    if (!$selectedCustomerAlreadyListed) {
        array_unshift($customerResults, $selectedCustomer);
    }
}

$ticketForm = [
    'consultation_type' => trim((string) ($_POST['consultation_type'] ?? 'Soporte')),
    'status' => trim((string) ($_POST['status'] ?? 'Nuevo')),
    'subject' => trim((string) ($_POST['subject'] ?? '')),
    'context_label' => trim((string) ($_POST['context_label'] ?? '')),
    'message' => trim((string) ($_POST['message'] ?? '')),
];

$customerForm = [
    'name' => trim((string) ($_POST['customer_name'] ?? '')),
    'business_name' => trim((string) ($_POST['customer_business_name'] ?? '')),
    'email' => trim((string) ($_POST['customer_email'] ?? '')),
    'phone' => trim((string) ($_POST['customer_phone'] ?? '')),
];

$error = '';

if (!in_array($ticketForm['consultation_type'], $allowedTypes, true)) {
    $ticketForm['consultation_type'] = 'Soporte';
}

if (!in_array($ticketForm['status'], $allowedStatuses, true)) {
    $ticketForm['status'] = 'Nuevo';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    if ($ticketForm['message'] === '') {
        $error = 'El mensaje del ticket es obligatorio.';
    } else {
        $customer = null;

        if ($customerMode === 'existing') {
            if ($selectedCustomerId <= 0) {
                $error = 'Selecciona un cliente existente para continuar.';
            } else {
                $customer = getCustomerById($conn, $selectedCustomerId);
                if (!$customer) {
                    $error = 'Selecciona un cliente válido.';
                }
            }
        } else {
            if ($customerForm['name'] === '' || $customerForm['phone'] === '') {
                $error = 'Para crear un cliente nuevo debes capturar nombre y teléfono.';
            } elseif (!is_valid_optional_email($customerForm['email'])) {
                $error = 'El correo del cliente no es válido.';
            } else {
                $customer = create_or_update_customer(
                    $conn,
                    $customerForm['name'],
                    $customerForm['email'],
                    $customerForm['phone'],
                    $customerForm['business_name']
                );

                if (!$customer) {
                    $error = 'No se pudo resolver o crear el cliente.';
                }
            }
        }

        if ($error === '') {
            $attachmentPath = '';
            $attachmentSave = save_ticket_attachment(
                'attachment',
                __DIR__ . '/../uploads/tickets',
                'uploads/tickets',
                image_upload_rules()
            );

            if (!$attachmentSave['ok']) {
                $error = $attachmentSave['error'];
            } else {
                $attachmentPath = (string) ($attachmentSave['path'] ?? '');
            }
        }

        if ($error === '') {
            $ticket = create_support_ticket($conn, [
                'customer_id' => $customer['id'],
                'consultation_type' => $ticketForm['consultation_type'],
                'status' => $ticketForm['status'],
                'origin_type' => 'admin',
                'product_id' => 0,
                'promo_id' => 0,
                'context_label' => $ticketForm['context_label'],
                'subject' => $ticketForm['subject'],
                'message' => $ticketForm['message'],
                'attachment_path' => $attachmentPath,
            ]);

            if ($ticket) {
                redirect_to('ticket_view.php?id=' . (int) $ticket['id'] . '&return=' . urlencode('tickets.php'));
            }

            $error = 'No se pudo crear el ticket en este momento.';
        }
    }
}
?>

<header class="admin-header">
    <div>
        <h2>Nuevo ticket</h2>
        <p>Crea un ticket manual y asígnalo a un cliente existente o nuevo desde la misma pantalla.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="tickets.php" class="btn btn--secondary">Volver a tickets</a>
    </div>
</header>

<div class="card panel-card">
    <?php if ($error): ?>
        <div class="alert alert--error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="customer_mode" id="customer_mode" value="<?php echo e($customerMode); ?>">
        <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $selectedCustomerId > 0 ? (int) $selectedCustomerId : ''; ?>">

        <section class="ticket-section">
            <div class="ticket-section__eyebrow">Cliente</div>
            <h3 class="ticket-section__title">Resolver cliente</h3>
            <div class="ticket-customer-mode" role="radiogroup" aria-label="Modo de cliente">
                <button
                    type="button"
                    class="ticket-customer-mode__option <?php echo $customerMode === 'existing' ? 'ticket-customer-mode__option--active' : ''; ?>"
                    data-customer-mode-option="existing"
                    aria-pressed="<?php echo $customerMode === 'existing' ? 'true' : 'false'; ?>"
                >
                    Seleccionar cliente existente
                </button>
                <button
                    type="button"
                    class="ticket-customer-mode__option <?php echo $customerMode === 'new' ? 'ticket-customer-mode__option--active' : ''; ?>"
                    data-customer-mode-option="new"
                    aria-pressed="<?php echo $customerMode === 'new' ? 'true' : 'false'; ?>"
                >
                    Crear cliente nuevo
                </button>
            </div>

            <div
                class="ticket-customer-panel <?php echo $customerMode !== 'existing' ? 'ticket-customer-panel--hidden' : ''; ?>"
                data-customer-panel="existing"
                <?php echo $customerMode !== 'existing' ? 'hidden' : ''; ?>
            >
                <p class="ticket-observations__intro">Busca y selecciona un cliente ya registrado para asociarlo al ticket.</p>
                <div class="ticket-customer-search">
                    <label class="admin-form__label ticket-customer-search__label" for="customer_query">Buscar cliente existente</label>
                    <div class="ticket-customer-search__field">
                        <span class="ticket-customer-search__icon" aria-hidden="true">
                            <i class="fas fa-search"></i>
                        </span>
                        <input
                            type="text"
                            id="customer_query"
                            name="customer_query"
                            class="admin-form__input ticket-customer-search__input"
                            value="<?php echo e($customerQuery); ?>"
                            placeholder="Nombre, razón social, correo o teléfono"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="<?php echo !empty($customerResults) ? 'true' : 'false'; ?>"
                            aria-controls="customer_results_list"
                            aria-haspopup="listbox"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <button type="button" class="ticket-customer-search__clear<?php echo $customerQuery === '' ? ' ticket-customer-search__clear--hidden' : ''; ?>" data-customer-clear <?php echo $customerQuery === '' ? 'hidden' : ''; ?> aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="ticket-customer-search__meta">
                        <p class="ticket-customer-results__hint">Busca por nombre, razón social, correo o teléfono. Se muestran hasta 5 coincidencias.</p>
                        <p class="ticket-customer-results__status" data-customer-results-status>
                            <?php if ($customerQuery !== '' && !empty($customerResults)): ?>
                                <?php echo min(count($customerResults), 5); ?> resultado<?php echo min(count($customerResults), 5) === 1 ? '' : 's'; ?>
                            <?php elseif ($customerQuery !== ''): ?>
                                Sin resultados
                            <?php else: ?>
                                Escribe al menos 3 caracteres
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="ticket-customer-results" data-customer-results id="customer_results_list" role="listbox">
                    <?php if (!empty($customerResults)): ?>
                        <?php foreach ($customerResults as $customerResult): ?>
                            <?php
                            $customerResultId = (int) $customerResult['id'];
                            $customerResultName = trim((string) ($customerResult['name'] ?? ''));
                            $customerResultBusinessName = trim((string) ($customerResult['business_name'] ?? ''));
                            $customerResultEmail = trim((string) ($customerResult['email'] ?? ''));
                            $customerResultPhone = trim((string) ($customerResult['phone'] ?? ''));
                            $customerSearchValue = strtolower(trim($customerResultName . ' ' . $customerResultBusinessName . ' ' . $customerResultEmail . ' ' . $customerResultPhone));
                            $isCustomerSelected = $selectedCustomerId === $customerResultId;
                            ?>
                            <button
                                type="button"
                                class="ticket-customer-result<?php echo $isCustomerSelected ? ' ticket-customer-result--selected' : ''; ?>"
                                data-customer-result
                                data-customer-id="<?php echo $customerResultId; ?>"
                                data-customer-name="<?php echo e($customerResultName); ?>"
                                data-customer-business="<?php echo e($customerResultBusinessName); ?>"
                                data-customer-email="<?php echo e($customerResultEmail); ?>"
                                data-customer-phone="<?php echo e($customerResultPhone); ?>"
                                data-customer-search="<?php echo e($customerSearchValue); ?>"
                                aria-pressed="<?php echo $isCustomerSelected ? 'true' : 'false'; ?>"
                                aria-selected="<?php echo $isCustomerSelected ? 'true' : 'false'; ?>"
                                role="option"
                                id="customer-result-<?php echo $customerResultId; ?>"
                            >
                                <span class="ticket-customer-result__indicator" aria-hidden="true"></span>
                                <span class="ticket-customer-result__body">
                                    <span class="ticket-customer-result__main">
                                        <span class="ticket-customer-result__name"><?php echo e($customerResultName); ?></span>
                                        <?php if ($customerResultBusinessName !== ''): ?>
                                            <span class="ticket-customer-result__business"><?php echo e($customerResultBusinessName); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ticket-customer-result__meta">
                                        <?php if ($customerResultEmail !== ''): ?>
                                            <span><?php echo e($customerResultEmail); ?></span>
                                        <?php endif; ?>
                                        <?php if ($customerResultPhone !== ''): ?>
                                            <span><?php echo e($customerResultPhone); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <span class="ticket-customer-result__action">Usar</span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div
                        class="ticket-customer-results__empty<?php echo !empty($customerResults) || $customerQuery === '' ? ' ticket-customer-results__empty--hidden' : ''; ?>"
                        data-customer-empty
                        <?php echo $customerQuery === '' ? 'hidden' : ''; ?>
                    >
                        No hay clientes registrados que coincidan con esta búsqueda.
                    </div>
                </div>
            </div>

            <div
                class="ticket-customer-selection<?php echo (!$selectedCustomer || $customerMode !== 'existing') ? ' ticket-customer-selection--hidden' : ''; ?>"
                data-customer-selection
                <?php echo (!$selectedCustomer || $customerMode !== 'existing') ? 'hidden' : ''; ?>
            >
                <span class="ticket-customer-selection__label">Usando cliente</span>
                <div class="ticket-customer-selection__summary">
                    <strong data-customer-selection-name><?php echo $selectedCustomer ? e($selectedCustomer['name']) : ''; ?></strong>
                    <span data-customer-selection-business>
                        <?php if ($selectedCustomer && trim((string) ($selectedCustomer['business_name'] ?? '')) !== ''): ?>
                            <?php echo e($selectedCustomer['business_name']); ?>
                        <?php endif; ?>
                    </span>
                    <span data-customer-selection-phone><?php echo $selectedCustomer ? e($selectedCustomer['phone']) : ''; ?></span>
                    <span data-customer-selection-email><?php echo $selectedCustomer ? e($selectedCustomer['email']) : ''; ?></span>
                </div>
            </div>
        </section>

        <section
            class="ticket-section <?php echo $customerMode !== 'new' ? 'ticket-customer-panel--hidden' : ''; ?>"
            data-customer-panel="new"
            <?php echo $customerMode !== 'new' ? 'hidden' : ''; ?>
        >
            <div class="ticket-section__eyebrow">Alta rápida</div>
            <h3 class="ticket-section__title">Datos del cliente</h3>
            <p class="ticket-observations__intro">Captura el cliente si aún no existe. El teléfono es obligatorio y se usa como identificador único del cliente.</p>
            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="customer_name">Nombre de contacto</label>
                    <input type="text" id="customer_name" name="customer_name" class="admin-form__input" data-customer-new-required value="<?php echo e($customerForm['name']); ?>">
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="customer_business_name">Razón social</label>
                    <input type="text" id="customer_business_name" name="customer_business_name" class="admin-form__input" value="<?php echo e($customerForm['business_name']); ?>">
                </div>
            </div>
            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="customer_email">Correo electrónico</label>
                    <input type="email" id="customer_email" name="customer_email" class="admin-form__input" value="<?php echo e($customerForm['email']); ?>">
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="customer_phone">Teléfono</label>
                    <input type="text" id="customer_phone" name="customer_phone" class="admin-form__input" data-customer-new-required value="<?php echo e($customerForm['phone']); ?>">
                </div>
            </div>
        </section>

        <section class="ticket-section">
            <div class="ticket-section__eyebrow">Ticket</div>
            <h3 class="ticket-section__title">Datos del ticket</h3>
            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="consultation_type">Tipo de consulta</label>
                    <select id="consultation_type" name="consultation_type" class="admin-form__input">
                        <?php foreach ($allowedTypes as $type): ?>
                            <option value="<?php echo e($type); ?>" <?php echo $ticketForm['consultation_type'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="status">Estado inicial</label>
                    <select id="status" name="status" class="admin-form__input">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $ticketForm['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="subject">Asunto</label>
                    <input type="text" id="subject" name="subject" class="admin-form__input" value="<?php echo e($ticketForm['subject']); ?>" placeholder="Resumen corto del ticket">
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="context_label">Referencia interna / contexto</label>
                    <input type="text" id="context_label" name="context_label" class="admin-form__input" value="<?php echo e($ticketForm['context_label']); ?>" placeholder="Referencia comercial, proyecto, folio, etc.">
                </div>
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="message">Mensaje</label>
                <textarea id="message" name="message" class="admin-form__input admin-form__input--textarea" rows="6" required><?php echo e($ticketForm['message']); ?></textarea>
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="attachment">Adjunto opcional</label>
                <input type="file" id="attachment" name="attachment" class="admin-form__input" accept="image/*">
            </div>
        </section>

        <button type="submit" class="btn">Crear ticket</button>
    </form>
</div>

</main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeInput = document.getElementById('customer_mode');
    const customerIdInput = document.getElementById('customer_id');
    const customerQueryInput = document.getElementById('customer_query');
    const customerClearButton = document.querySelector('[data-customer-clear]');
    const options = document.querySelectorAll('[data-customer-mode-option]');
    const panels = document.querySelectorAll('[data-customer-panel]');
    const customerResultsContainer = document.querySelector('[data-customer-results]');
    const customerEmptyState = document.querySelector('[data-customer-empty]');
    const customerResultsStatus = document.querySelector('[data-customer-results-status]');
    const customerSelection = document.querySelector('[data-customer-selection]');
    const customerSelectionName = document.querySelector('[data-customer-selection-name]');
    const customerSelectionBusiness = document.querySelector('[data-customer-selection-business]');
    const customerSelectionPhone = document.querySelector('[data-customer-selection-phone]');
    const customerSelectionEmail = document.querySelector('[data-customer-selection-email]');
    const customerNewRequiredFields = document.querySelectorAll('[data-customer-new-required]');
    const minQueryLength = 3;
    const maxVisibleResults = 5;
    const searchEndpoint = 'customer_search.php';
    let customerResults = Array.from(document.querySelectorAll('[data-customer-result]'));
    let activeResultIndex = -1;
    let debounceTimer = null;
    let searchAbortController = null;
    let latestSearchQuery = '';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setResultsStatus(message) {
        if (customerResultsStatus) {
            customerResultsStatus.textContent = message;
        }
    }

    function setComboboxExpanded(isExpanded) {
        if (customerQueryInput) {
            customerQueryInput.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        }
    }

    function clearActiveDescendant() {
        if (customerQueryInput) {
            customerQueryInput.removeAttribute('aria-activedescendant');
        }
    }

    function attachCustomerResultEvents(button) {
        button.addEventListener('click', function () {
            applyCustomerSelection(button.dataset.customerId || '');
        });
    }

    function buildCustomerResultButton(customer) {
        const button = document.createElement('button');
        const isSelected = customerIdInput && customerIdInput.value !== '' && customerIdInput.value === String(customer.id);
        const metaParts = [];

        if (customer.email) {
            metaParts.push('<span>' + escapeHtml(customer.email) + '</span>');
        }
        if (customer.phone) {
            metaParts.push('<span>' + escapeHtml(customer.phone) + '</span>');
        }

        button.type = 'button';
        button.className = 'ticket-customer-result' + (isSelected ? ' ticket-customer-result--selected' : '');
        button.dataset.customerResult = '';
        button.dataset.customerId = String(customer.id || '');
        button.dataset.customerName = customer.name || '';
        button.dataset.customerBusiness = customer.business_name || '';
        button.dataset.customerEmail = customer.email || '';
        button.dataset.customerPhone = customer.phone || '';
        button.dataset.customerSearch = ((customer.name || '') + ' ' + (customer.business_name || '') + ' ' + (customer.email || '') + ' ' + (customer.phone || '')).trim().toLowerCase();
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        button.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        button.setAttribute('role', 'option');
        button.id = 'customer-result-' + customer.id;
        button.innerHTML =
            '<span class="ticket-customer-result__indicator" aria-hidden="true"></span>' +
            '<span class="ticket-customer-result__body">' +
                '<span class="ticket-customer-result__main">' +
                    '<span class="ticket-customer-result__name">' + escapeHtml(customer.name || '') + '</span>' +
                    ((customer.business_name || '') !== '' ? '<span class="ticket-customer-result__business">' + escapeHtml(customer.business_name) + '</span>' : '') +
                '</span>' +
                '<span class="ticket-customer-result__meta">' + metaParts.join('') + '</span>' +
            '</span>' +
            '<span class="ticket-customer-result__action">Usar</span>';

        attachCustomerResultEvents(button);
        return button;
    }

    function refreshCustomerResultsCollection() {
        customerResults = Array.from(document.querySelectorAll('[data-customer-result]'));
    }

    function clearCustomerResults() {
        customerResults.forEach((result) => result.remove());
        customerResults = [];
        activeResultIndex = -1;
        clearActiveDescendant();
        setComboboxExpanded(false);
    }

    function renderCustomerResults(results) {
        if (!customerResultsContainer) {
            return;
        }

        clearCustomerResults();

        results.slice(0, maxVisibleResults).forEach((customer) => {
            const button = buildCustomerResultButton(customer);
            customerResultsContainer.insertBefore(button, customerEmptyState);
        });

        refreshCustomerResultsCollection();

        const hasResults = customerResults.length > 0;
        if (customerEmptyState) {
            customerEmptyState.classList.toggle('ticket-customer-results__empty--hidden', hasResults);
            customerEmptyState.hidden = hasResults;
        }
        setComboboxExpanded(hasResults);
    }

    function setEmptyState(message, shouldShow) {
        if (!customerEmptyState) {
            return;
        }

        customerEmptyState.textContent = message;
        customerEmptyState.classList.toggle('ticket-customer-results__empty--hidden', !shouldShow);
        customerEmptyState.hidden = !shouldShow;
    }

    function updateActiveResult(nextIndex) {
        if (!customerResults.length) {
            activeResultIndex = -1;
            clearActiveDescendant();
            return;
        }

        if (nextIndex < 0) {
            nextIndex = customerResults.length - 1;
        } else if (nextIndex >= customerResults.length) {
            nextIndex = 0;
        }

        activeResultIndex = nextIndex;

        customerResults.forEach((result, index) => {
            const isActive = index === activeResultIndex;
            const isSelected = customerIdInput && result.dataset.customerId === customerIdInput.value;
            result.classList.toggle('ticket-customer-result--selected', isActive || isSelected);
            result.setAttribute('aria-selected', isActive ? 'true' : 'false');

            if (isActive) {
                result.scrollIntoView({ block: 'nearest' });
                if (customerQueryInput) {
                    customerQueryInput.setAttribute('aria-activedescendant', result.id);
                }
            }
        });
    }

    function abortActiveSearch() {
        if (searchAbortController) {
            searchAbortController.abort();
            searchAbortController = null;
        }
    }

    function applyCustomerMode(nextMode) {
        modeInput.value = nextMode;

        options.forEach((option) => {
            const isActive = option.dataset.customerModeOption === nextMode;
            option.classList.toggle('ticket-customer-mode__option--active', isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            const isActive = panel.dataset.customerPanel === nextMode;
            panel.classList.toggle('ticket-customer-panel--hidden', !isActive);
            panel.hidden = !isActive;
        });

        customerNewRequiredFields.forEach((field) => {
            field.required = nextMode === 'new';
        });

        if (customerSelection) {
            const hasSelection = customerIdInput && customerIdInput.value !== '';
            const shouldShowSelection = nextMode === 'existing' && hasSelection;
            customerSelection.classList.toggle('ticket-customer-selection--hidden', !shouldShowSelection);
            customerSelection.hidden = !shouldShowSelection;
        }

        if (nextMode !== 'existing') {
            abortActiveSearch();
            clearActiveDescendant();
        }
    }

    function updateCustomerSelectionSummary(result) {
        if (!customerSelection || !customerSelectionName || !customerSelectionBusiness || !customerSelectionPhone || !customerSelectionEmail) {
            return;
        }

        if (!result || !customerIdInput || customerIdInput.value === '') {
            customerSelection.classList.add('ticket-customer-selection--hidden');
            customerSelection.hidden = true;
            customerSelectionName.textContent = '';
            customerSelectionBusiness.textContent = '';
            customerSelectionPhone.textContent = '';
            customerSelectionEmail.textContent = '';
            return;
        }

        customerSelection.classList.remove('ticket-customer-selection--hidden');
        customerSelection.hidden = false;
        customerSelectionName.textContent = result.dataset.customerName || '';
        customerSelectionBusiness.textContent = result.dataset.customerBusiness || '';
        customerSelectionPhone.textContent = result.dataset.customerPhone || '';
        customerSelectionEmail.textContent = result.dataset.customerEmail || '';
    }

    function applyCustomerSelection(customerId) {
        let selectedResult = null;

        customerResults.forEach((result) => {
            const isSelected = result.dataset.customerId === customerId && customerId !== '';
            result.classList.toggle('ticket-customer-result--selected', isSelected);
            result.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            result.setAttribute('aria-selected', isSelected ? 'true' : 'false');

            if (isSelected) {
                selectedResult = result;
            }
        });

        if (customerIdInput) {
            customerIdInput.value = customerId;
        }

        updateCustomerSelectionSummary(selectedResult);
        activeResultIndex = selectedResult ? customerResults.indexOf(selectedResult) : -1;
        if (activeResultIndex >= 0) {
            updateActiveResult(activeResultIndex);
        } else {
            clearActiveDescendant();
        }
    }

    function handleCustomerQueryInput() {
        if (!customerQueryInput) {
            return;
        }

        const query = customerQueryInput.value.trim();
        latestSearchQuery = query;
        activeResultIndex = -1;
        clearActiveDescendant();

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        if (customerClearButton) {
            const shouldShowClear = query !== '';
            customerClearButton.classList.toggle('ticket-customer-search__clear--hidden', !shouldShowClear);
            customerClearButton.hidden = !shouldShowClear;
        }

        if (query.length < minQueryLength) {
            abortActiveSearch();
            clearCustomerResults();
            setEmptyState('No hay clientes registrados que coincidan con esta búsqueda.', false);
            setResultsStatus('Escribe al menos 3 caracteres');
            return;
        }

        setResultsStatus('Buscando clientes...');
        setEmptyState('No hay clientes registrados que coincidan con esta búsqueda.', false);

        debounceTimer = window.setTimeout(function () {
            abortActiveSearch();
            searchAbortController = new AbortController();

            fetch(searchEndpoint + '?q=' + encodeURIComponent(query), {
                method: 'GET',
                credentials: 'same-origin',
                signal: searchAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('request_failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    if (!customerQueryInput || customerQueryInput.value.trim() !== query) {
                        return;
                    }

                    const results = Array.isArray(payload.results) ? payload.results : [];
                    if (results.length === 0) {
                        clearCustomerResults();
                        setEmptyState('No hay clientes registrados que coincidan con esta búsqueda.', true);
                        setResultsStatus('Sin resultados');
                        return;
                    }

                    renderCustomerResults(results);
                    setResultsStatus(results.length + ' resultado' + (results.length === 1 ? '' : 's'));
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    clearCustomerResults();
                    setEmptyState('Ocurrió un problema al buscar clientes. Intenta nuevamente.', true);
                    setResultsStatus('No se pudo completar la búsqueda');
                })
                .finally(function () {
                    searchAbortController = null;
                });
        }, 300);
    }

    options.forEach((option) => {
        option.addEventListener('click', function () {
            applyCustomerMode(option.dataset.customerModeOption);
        });
    });

    customerResults.forEach((result) => {
        result.addEventListener('click', function () {
            applyCustomerSelection(result.dataset.customerId || '');
        });
    });

    if (customerQueryInput) {
        customerQueryInput.addEventListener('input', handleCustomerQueryInput);
        customerQueryInput.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                if (!customerResults.length) {
                    return;
                }
                event.preventDefault();
                updateActiveResult(activeResultIndex + 1);
            } else if (event.key === 'ArrowUp') {
                if (!customerResults.length) {
                    return;
                }
                event.preventDefault();
                updateActiveResult(activeResultIndex - 1);
            } else if (event.key === 'Enter') {
                if (activeResultIndex >= 0 && customerResults[activeResultIndex]) {
                    event.preventDefault();
                    applyCustomerSelection(customerResults[activeResultIndex].dataset.customerId || '');
                }
            } else if (event.key === 'Escape') {
                abortActiveSearch();
                clearCustomerResults();
                setEmptyState('No hay clientes registrados que coincidan con esta búsqueda.', false);
                setResultsStatus(customerQueryInput.value.trim().length < minQueryLength ? 'Escribe al menos 3 caracteres' : 'Búsqueda cerrada');
            }
        });
    }

    if (customerClearButton && customerQueryInput) {
        customerClearButton.addEventListener('click', function () {
            abortActiveSearch();
            customerQueryInput.value = '';
            customerQueryInput.focus();
            handleCustomerQueryInput();
        });
    }

    applyCustomerSelection(customerIdInput ? customerIdInput.value : '');
    handleCustomerQueryInput();
    applyCustomerMode(modeInput.value || 'new');
});
</script>
</body>
</html>
