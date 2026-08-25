<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$viewerUserId = current_admin_id();
$viewerRole = current_admin_role();

$allowedStatuses = ['Nuevo', 'Agendado', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];
$allowedTypes = ['Compra', 'Soporte', 'Informacion'];
$allowedScopes = ['open', 'all'];
$allowedPageSizes = [10, 25, 50];
$defaultPageSize = 10;
$unassignedResponsibleFilter = 'unassigned';
$assignableUsers = [];

$filters = [
    'scope' => trim((string) ($_GET['scope'] ?? 'open')),
    'customer' => trim((string) ($_GET['customer'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'consultation_type' => trim((string) ($_GET['consultation_type'] ?? '')),
    'assigned_user_id' => trim((string) ($_GET['assigned_user_id'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$perPage = (int) ($_GET['per_page'] ?? $defaultPageSize);
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

if (!in_array($filters['status'], $allowedStatuses, true)) {
    $filters['status'] = '';
}

if (!in_array($filters['consultation_type'], $allowedTypes, true)) {
    $filters['consultation_type'] = '';
}

if (!in_array($filters['scope'], $allowedScopes, true)) {
    $filters['scope'] = 'open';
}

if ($viewerRole === 'admin') {
    $assignableUsersResult = getAdminUsers($conn, true);
    while ($assignableUsersResult && $user = $assignableUsersResult->fetch_assoc()) {
        $assignableUsers[(string) $user['id']] = $user;
    }

    $selectedResponsible = $filters['assigned_user_id'];
    if ($selectedResponsible !== '' && $selectedResponsible !== $unassignedResponsibleFilter && !isset($assignableUsers[$selectedResponsible])) {
        $filters['assigned_user_id'] = '';
    }
} else {
    $filters['assigned_user_id'] = '';
}

if (!in_array($perPage, $allowedPageSizes, true)) {
    $perPage = $defaultPageSize;
}

$ticketStats = getTicketStats($conn, $viewerUserId, $viewerRole);
$totalTickets = getSupportTicketsCount($conn, $filters, $viewerUserId, $viewerRole);
$totalPages = max(1, (int) ceil($totalTickets / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$tickets = getSupportTickets($conn, $filters, $perPage, $offset, $viewerUserId, $viewerRole);
$ticketsCount = $tickets ? $tickets->num_rows : 0;
$activeFilters = [];

if ($filters['customer'] !== '') {
    $activeFilters[] = 'Cliente: ' . $filters['customer'];
}
if ($filters['status'] !== '') {
    $activeFilters[] = 'Estado: ' . $filters['status'];
}
if ($filters['consultation_type'] !== '') {
    $activeFilters[] = 'Tipo: ' . $filters['consultation_type'];
}
if ($filters['assigned_user_id'] !== '') {
    if ($filters['assigned_user_id'] === $unassignedResponsibleFilter) {
        $activeFilters[] = 'Responsable: Sin responsable';
    } elseif (isset($assignableUsers[$filters['assigned_user_id']])) {
        $responsible = $assignableUsers[$filters['assigned_user_id']];
        $responsibleName = trim((string) ($responsible['display_name'] ?? '')) !== ''
            ? (string) $responsible['display_name']
            : (string) ($responsible['username'] ?? '');
        $activeFilters[] = 'Responsable: ' . $responsibleName;
    }
}
if ($filters['date_from'] !== '') {
    $activeFilters[] = 'Desde: ' . $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $activeFilters[] = 'Hasta: ' . $filters['date_to'];
}

function ticket_origin_label($originType) {
    $map = [
        'contact' => 'Contacto',
        'product' => 'Producto',
        'promotion' => 'Promocion',
        'admin' => 'Admin',
    ];

    return $map[$originType] ?? ucfirst((string) $originType);
}

function build_ticket_page_url(array $filters, $page) {
    global $perPage, $defaultPageSize;
    $params = array_filter([
        'scope' => ($filters['scope'] ?? 'open') !== 'open' ? $filters['scope'] : null,
        'customer' => $filters['customer'],
        'status' => $filters['status'],
        'consultation_type' => $filters['consultation_type'],
        'assigned_user_id' => $filters['assigned_user_id'],
        'date_from' => $filters['date_from'],
        'date_to' => $filters['date_to'],
        'per_page' => $perPage !== $defaultPageSize ? $perPage : null,
        'page' => $page > 1 ? $page : null,
    ], static function ($value) {
        return $value !== null && $value !== '';
    });

    return 'tickets.php' . ($params ? '?' . http_build_query($params) : '');
}

$backToListUrl = build_ticket_page_url($filters, $currentPage);
$paginationStart = max(1, $currentPage - 2);
$paginationEnd = min($totalPages, $currentPage + 2);
$isOpenScope = $filters['scope'] === 'open';
$scopeTitle = $isOpenScope ? 'Tickets abiertos' : 'Todos los tickets';
$scopeDescription = $isOpenScope
    ? 'Vista operativa con tickets cuyo estado no es Resuelto ni Cerrado.'
    : 'Vista histórica completa con tickets de todos los estados, incluidos Resuelto y Cerrado.';
$clearFiltersUrl = build_ticket_page_url([
    'scope' => $filters['scope'],
    'customer' => '',
    'status' => '',
    'consultation_type' => '',
    'assigned_user_id' => '',
    'date_from' => '',
    'date_to' => '',
], 1);
$ticketFlash = get_flash_message('ticket_notice');
$ticketNotice = trim((string) ($ticketFlash['message'] ?? ''));
$ticketNoticeType = trim((string) ($ticketFlash['type'] ?? 'success'));
if (!in_array($ticketNoticeType, ['success', 'error'], true)) {
    $ticketNoticeType = 'success';
}
?>

<header class="admin-header">
    <div>
        <h2><?php echo e($scopeTitle); ?></h2>
        <p><?php echo e($scopeDescription); ?></p>
    </div>
    <div class="ticket-header-actions">
        <a href="ticket_create.php" class="btn">Nuevo ticket</a>
        <a href="customers.php" class="btn btn--secondary">Ver clientes</a>
    </div>
</header>

<nav class="ticket-scope-tabs" aria-label="Vistas de tickets">
    <a
        href="<?php echo e(build_ticket_page_url(array_merge($filters, ['scope' => 'open']), 1)); ?>"
        class="ticket-scope-tab <?php echo $isOpenScope ? 'ticket-scope-tab--active' : ''; ?>"
        <?php echo $isOpenScope ? 'aria-current="page"' : ''; ?>
    >
        Tickets abiertos
    </a>
    <a
        href="<?php echo e(build_ticket_page_url(array_merge($filters, ['scope' => 'all']), 1)); ?>"
        class="ticket-scope-tab <?php echo !$isOpenScope ? 'ticket-scope-tab--active' : ''; ?>"
        <?php echo !$isOpenScope ? 'aria-current="page"' : ''; ?>
    >
        Todos los tickets
    </a>
</nav>

<?php if ($ticketNotice !== ''): ?>
    <div class="alert alert--<?php echo e($ticketNoticeType); ?>" data-auto-dismiss="5000"><?php echo e($ticketNotice); ?></div>
<?php endif; ?>

<div class="stats">
    <div class="stat-card">
        <i class="fas fa-life-ring stat-card__icon" style="color: #d97706;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['total']; ?></div>
        <p class="stat-card__label">Total Tickets</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-hourglass-half stat-card__icon" style="color: #dc2626;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['open']; ?></div>
        <p class="stat-card__label">Abiertos</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-friends stat-card__icon" style="color: #1d4ed8;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['customers']; ?></div>
        <p class="stat-card__label">Clientes</p>
    </div>
</div>

<div class="card panel-card" style="margin-bottom: 3rem;">
    <form method="GET" class="admin-form">
        <div class="admin-form__row admin-form__row--triple">
            <div class="admin-form__group">
                <label class="admin-form__label" for="customer">Cliente</label>
                <input type="text" id="customer" name="customer" class="admin-form__input" value="<?php echo e($filters['customer']); ?>" placeholder="Nombre, razón social, correo o teléfono">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="status">Estado</label>
                <select id="status" name="status" class="admin-form__input">
                    <option value="">Todos</option>
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?php echo e($status); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="consultation_type">Tipo</label>
                <select id="consultation_type" name="consultation_type" class="admin-form__input">
                    <option value="">Todos</option>
                    <?php foreach ($allowedTypes as $type): ?>
                        <option value="<?php echo e($type); ?>" <?php echo $filters['consultation_type'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="admin-form__row admin-form__row--triple">
            <div class="admin-form__group">
                <label class="admin-form__label" for="date_from">Desde</label>
                <input type="date" id="date_from" name="date_from" class="admin-form__input" value="<?php echo e($filters['date_from']); ?>">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="date_to">Hasta</label>
                <input type="date" id="date_to" name="date_to" class="admin-form__input" value="<?php echo e($filters['date_to']); ?>">
            </div>
            <?php if ($viewerRole === 'admin'): ?>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="assigned_user_id">Responsable</label>
                    <select id="assigned_user_id" name="assigned_user_id" class="admin-form__input">
                        <option value="">Todos</option>
                        <option value="<?php echo e($unassignedResponsibleFilter); ?>" <?php echo $filters['assigned_user_id'] === $unassignedResponsibleFilter ? 'selected' : ''; ?>>Sin responsable</option>
                        <?php foreach ($assignableUsers as $assignableUser): ?>
                            <?php
                                $assignableUserId = (string) ($assignableUser['id'] ?? '');
                                $assignableUserLabel = trim((string) ($assignableUser['display_name'] ?? '')) !== ''
                                    ? (string) $assignableUser['display_name']
                                    : (string) ($assignableUser['username'] ?? '');
                            ?>
                            <option value="<?php echo e($assignableUserId); ?>" <?php echo $filters['assigned_user_id'] === $assignableUserId ? 'selected' : ''; ?>>
                                <?php echo e($assignableUserLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="per_page">Mostrar</label>
                    <select id="per_page" name="per_page" class="admin-form__input">
                        <?php foreach ($allowedPageSizes as $pageSize): ?>
                            <option value="<?php echo $pageSize; ?>" <?php echo $perPage === $pageSize ? 'selected' : ''; ?>><?php echo $pageSize; ?> por pagina</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($viewerRole === 'admin'): ?>
            <div class="admin-form__row admin-form__row--triple">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="per_page">Mostrar</label>
                    <select id="per_page" name="per_page" class="admin-form__input">
                        <?php foreach ($allowedPageSizes as $pageSize): ?>
                            <option value="<?php echo $pageSize; ?>" <?php echo $perPage === $pageSize ? 'selected' : ''; ?>><?php echo $pageSize; ?> por pagina</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>
        <div class="admin-form__row admin-form__row--actions">
            <div class="admin-form__group admin-form__actions admin-form__actions--tickets">
                <button type="submit" class="btn">Filtrar</button>
                <a href="<?php echo e($clearFiltersUrl); ?>" class="btn btn--secondary">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<div class="ticket-toolbar">
    <div class="ticket-results-summary">
        <strong><?php echo $totalTickets; ?></strong> ticket<?php echo $totalTickets === 1 ? '' : 's'; ?> encontrado<?php echo $totalTickets === 1 ? '' : 's'; ?> en <?php echo e($scopeTitle); ?>
        <span class="ticket-results-summary__page">Pagina <?php echo $currentPage; ?> de <?php echo $totalPages; ?>, <?php echo $perPage; ?> por pagina</span>
    </div>
    <?php if ($activeFilters): ?>
        <div class="filter-chips" aria-label="Filtros activos">
            <?php foreach ($activeFilters as $filterLabel): ?>
                <span class="filter-chip"><?php echo e($filterLabel); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Paginacion de tickets">
        <?php if ($currentPage > 1): ?>
            <a href="<?php echo e(build_ticket_page_url($filters, $currentPage - 1)); ?>" class="pagination__link">Anterior</a>
        <?php endif; ?>

        <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
            <a
                href="<?php echo e(build_ticket_page_url($filters, $page)); ?>"
                class="pagination__link <?php echo $page === $currentPage ? 'pagination__link--active' : ''; ?>"
                <?php echo $page === $currentPage ? 'aria-current="page"' : ''; ?>
            >
                <?php echo $page; ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo e(build_ticket_page_url($filters, $currentPage + 1)); ?>" class="pagination__link">Siguiente</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<div class="table-wrapper">
    <table class="table table--tickets">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Cliente</th>
                <th>Responsable</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Cita</th>
                <th>Canal</th>
                <th>Contexto</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tickets && $tickets->num_rows > 0): ?>
                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="ticket_view.php?id=<?php echo (int) $ticket['id']; ?>&return=<?php echo urlencode($backToListUrl); ?>" class="ticket-primary-link">
                                #<?php echo (int) $ticket['id']; ?>
                            </a>
                            <div class="ticket-meta"><?php echo e($ticket['consultation_type']); ?></div>
                        </td>
                        <td>
                            <strong><?php echo e($ticket['customer_name']); ?></strong>
                            <?php if (trim((string) ($ticket['customer_business_name'] ?? '')) !== ''): ?>
                                <div class="ticket-meta"><?php echo e($ticket['customer_business_name']); ?></div>
                            <?php endif; ?>
                            <?php if (trim((string) ($ticket['customer_phone'] ?? '')) !== ''): ?>
                                <div class="ticket-meta"><?php echo e($ticket['customer_phone']); ?></div>
                            <?php endif; ?>
                            <?php if (trim((string) ($ticket['customer_email'] ?? '')) !== ''): ?>
                                <div class="ticket-meta"><?php echo e($ticket['customer_email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $assignedDisplayLabel = trim((string) ($ticket['assigned_display_name'] ?? '')) !== ''
                                ? $ticket['assigned_display_name']
                                : trim((string) ($ticket['assigned_username'] ?? ''));
                            ?>
                            <?php if ($ticket['assigned_user_id'] && $assignedDisplayLabel !== ''): ?>
                                <span class="ticket-assignee"><?php echo e($assignedDisplayLabel); ?></span>
                            <?php else: ?>
                                <span class="ticket-muted">Sin responsable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (get_ticket_effective_price_amount($ticket) !== null): ?>
                                <span class="ticket-price"><?php echo e(format_ticket_price(get_ticket_effective_price_amount($ticket), $ticket['price_currency'] ?? 'MXN')); ?></span>
                            <?php else: ?>
                                <span class="ticket-muted">Sin precio asignado</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="ticket-status ticket-status--<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><span class="ticket-status__dot" aria-hidden="true"></span><?php echo e($ticket['status']); ?></span></td>
                        <td>
                            <?php if (trim((string) ($ticket['appointment_date'] ?? '')) !== '' && trim((string) ($ticket['appointment_time'] ?? '')) !== ''): ?>
                                <span><?php echo e(format_ticket_appointment_datetime($ticket['appointment_date'], $ticket['appointment_time'])); ?></span>
                            <?php else: ?>
                                <span class="ticket-muted">Sin cita</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(ticket_origin_label($ticket['origin_type'])); ?></td>
                        <td><?php echo e($ticket['context_label'] ?: 'Sin contexto adicional'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                        <td class="table__actions">
                            <a href="ticket_view.php?id=<?php echo (int) $ticket['id']; ?>&return=<?php echo urlencode($backToListUrl); ?>" class="table__action">Ver ticket</a>
                            <a href="customer_view.php?id=<?php echo (int) $ticket['customer_id']; ?>&return=<?php echo urlencode($backToListUrl); ?>" class="table__action">Ver cliente</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">No se encontraron tickets con los filtros seleccionados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
