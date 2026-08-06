<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$allowedStatuses = ['Nuevo', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];
$allowedTypes = ['Compra', 'Soporte', 'Informacion'];
$allowedPageSizes = [10, 25, 50];
$defaultPageSize = 10;

$filters = [
    'customer' => trim((string) ($_GET['customer'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'consultation_type' => trim((string) ($_GET['consultation_type'] ?? '')),
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

if (!in_array($perPage, $allowedPageSizes, true)) {
    $perPage = $defaultPageSize;
}

$ticketStats = getTicketStats($conn);
$totalTickets = getSupportTicketsCount($conn, $filters);
$totalPages = max(1, (int) ceil($totalTickets / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$tickets = getSupportTickets($conn, $filters, $perPage, $offset);
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
        'customer' => $filters['customer'],
        'status' => $filters['status'],
        'consultation_type' => $filters['consultation_type'],
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
?>

<header class="admin-header">
    <div>
        <h2>Tickets de Soporte</h2>
        <p>Seguimiento centralizado de solicitudes creadas desde el sitio y el panel administrativo.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="ticket_create.php" class="btn">Nuevo ticket</a>
        <a href="customers.php" class="btn btn--secondary">Ver clientes</a>
    </div>
</header>

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
            <div class="admin-form__group">
                <label class="admin-form__label" for="per_page">Mostrar</label>
                <select id="per_page" name="per_page" class="admin-form__input">
                    <?php foreach ($allowedPageSizes as $pageSize): ?>
                        <option value="<?php echo $pageSize; ?>" <?php echo $perPage === $pageSize ? 'selected' : ''; ?>><?php echo $pageSize; ?> por pagina</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="admin-form__row admin-form__row--actions">
            <div class="admin-form__group admin-form__actions admin-form__actions--tickets">
                <button type="submit" class="btn">Filtrar</button>
                <a href="tickets.php" class="btn btn--secondary">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<div class="ticket-toolbar">
    <div class="ticket-results-summary">
        <strong><?php echo $totalTickets; ?></strong> ticket<?php echo $totalTickets === 1 ? '' : 's'; ?> encontrado<?php echo $totalTickets === 1 ? '' : 's'; ?>
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
                <th>Estado</th>
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
                        <td><span class="ticket-status ticket-status--<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><span class="ticket-status__dot" aria-hidden="true"></span><?php echo e($ticket['status']); ?></span></td>
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
                    <td colspan="7">No se encontraron tickets con los filtros seleccionados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
