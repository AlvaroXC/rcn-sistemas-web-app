<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$search = trim((string) ($_GET['search'] ?? ''));
$allowedPageSizes = [10, 25, 50];
$defaultPageSize = 10;
$perPage = (int) ($_GET['per_page'] ?? $defaultPageSize);
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

if (!in_array($perPage, $allowedPageSizes, true)) {
    $perPage = $defaultPageSize;
}

$totalCustomers = getCustomersCount($conn, $search);
$totalPages = max(1, (int) ceil($totalCustomers / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$customers = getCustomers($conn, $search, $perPage, $offset);
$customerCount = $customers ? $customers->num_rows : 0;
$identityDiagnostics = get_customer_phone_identity_diagnostics($conn);

function build_customer_page_url($search, $page, $perPage) {
    global $defaultPageSize;

    $params = array_filter([
        'search' => $search,
        'per_page' => $perPage !== $defaultPageSize ? $perPage : null,
        'page' => $page > 1 ? $page : null,
    ], static function ($value) {
        return $value !== null && $value !== '';
    });

    return 'customers.php' . ($params ? '?' . http_build_query($params) : '');
}

$backToListUrl = build_customer_page_url($search, $currentPage, $perPage);
$paginationStart = max(1, $currentPage - 2);
$paginationEnd = min($totalPages, $currentPage + 2);
?>

<header class="admin-header">
    <div>
        <h2>Clientes</h2>
        <p>Gestiona contactos y razones sociales asociadas al módulo de tickets.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="customer_create.php" class="btn">Nuevo cliente</a>
        <a href="ticket_create.php" class="btn btn--secondary">Nuevo ticket</a>
    </div>
</header>

<?php if (!$identityDiagnostics['ready_for_enforcement']): ?>
    <div class="alert alert--info" style="margin-bottom: 2rem;">
        <strong>Migración pendiente a identidad por teléfono.</strong>
        <?php if ($identityDiagnostics['missing_phone_count'] > 0): ?>
            <div>Clientes sin teléfono: <?php echo (int) $identityDiagnostics['missing_phone_count']; ?>.</div>
            <?php foreach ($identityDiagnostics['missing_phone_customers'] as $missingCustomer): ?>
                <div>#<?php echo (int) $missingCustomer['id']; ?> · <?php echo e($missingCustomer['name']); ?><?php echo trim((string) ($missingCustomer['business_name'] ?? '')) !== '' ? ' · ' . e($missingCustomer['business_name']) : ''; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($identityDiagnostics['duplicate_group_count'] > 0): ?>
            <div>Teléfonos duplicados detectados: <?php echo (int) $identityDiagnostics['duplicate_group_count']; ?> grupo<?php echo $identityDiagnostics['duplicate_group_count'] === 1 ? '' : 's'; ?>.</div>
            <?php foreach ($identityDiagnostics['duplicate_phone_groups'] as $duplicateGroup): ?>
                <div>Teléfono <?php echo e($duplicateGroup['phone_normalized']); ?> en clientes <?php echo e($duplicateGroup['customer_ids']); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div>Resuelve estos registros antes de aplicar la restricción final de unicidad por teléfono.</div>
    </div>
<?php endif; ?>

<div class="card panel-card" style="margin-bottom: 3rem;">
    <form method="GET" class="admin-form">
        <div class="admin-form__row admin-form__row--triple">
            <div class="admin-form__group">
                <label class="admin-form__label" for="search">Buscar cliente</label>
                <input type="text" id="search" name="search" class="admin-form__input" value="<?php echo e($search); ?>" placeholder="Nombre, razón social, correo o teléfono">
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
                <button type="submit" class="btn">Buscar</button>
                <a href="customers.php" class="btn btn--secondary">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<div class="ticket-toolbar">
    <div class="ticket-results-summary">
        <strong><?php echo $totalCustomers; ?></strong> cliente<?php echo $totalCustomers === 1 ? '' : 's'; ?> encontrado<?php echo $totalCustomers === 1 ? '' : 's'; ?>
        <span class="ticket-results-summary__page">Pagina <?php echo $currentPage; ?> de <?php echo $totalPages; ?>, <?php echo $perPage; ?> por pagina</span>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Paginacion de clientes">
        <?php if ($currentPage > 1): ?>
            <a href="<?php echo e(build_customer_page_url($search, $currentPage - 1, $perPage)); ?>" class="pagination__link">Anterior</a>
        <?php endif; ?>

        <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
            <a
                href="<?php echo e(build_customer_page_url($search, $page, $perPage)); ?>"
                class="pagination__link <?php echo $page === $currentPage ? 'pagination__link--active' : ''; ?>"
                <?php echo $page === $currentPage ? 'aria-current="page"' : ''; ?>
            >
                <?php echo $page; ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo e(build_customer_page_url($search, $currentPage + 1, $perPage)); ?>" class="pagination__link">Siguiente</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<div class="table-wrapper">
    <table class="table table--tickets">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Razón social</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Registrado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($customers && $customers->num_rows > 0): ?>
                <?php while ($customer = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo e($customer['name']); ?></strong></td>
                        <td><?php echo e($customer['business_name'] ?: 'No proporcionada'); ?></td>
                        <td><?php echo e(trim((string) ($customer['email'] ?? '')) !== '' ? $customer['email'] : 'No proporcionado'); ?></td>
                        <td><?php echo e($customer['phone'] ?: 'No proporcionado'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></td>
                        <td class="table__actions">
                            <a href="customer_view.php?id=<?php echo (int) $customer['id']; ?>&return=<?php echo urlencode($backToListUrl); ?>" class="table__action">Ver cliente</a>
                            <a href="customer_edit.php?id=<?php echo (int) $customer['id']; ?>&return=<?php echo urlencode($backToListUrl); ?>" class="table__action">Editar cliente</a>
                            <a href="ticket_create.php?customer_id=<?php echo (int) $customer['id']; ?>" class="table__action">Nuevo ticket</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No se encontraron clientes con los criterios seleccionados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
