<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$customer = $customerId > 0 ? getCustomerById($conn, $customerId) : null;
$returnUrl = trim((string) ($_GET['return'] ?? 'tickets.php'));
$updated = (int) ($_GET['updated'] ?? 0) === 1;

if ($returnUrl === '' || strpos($returnUrl, 'http') === 0) {
    $returnUrl = 'tickets.php';
}

if (!$customer) {
    ?>
    <header class="admin-header">
        <h2>Cliente no encontrado</h2>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a tickets</a>
    </header>
    <div class="card panel-card">
        <p>El cliente solicitado no existe o ya no está disponible.</p>
    </div>
    </main>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$tickets = getCustomerTickets($conn, $customerId);
$ticketCount = $tickets ? $tickets->num_rows : 0;
$openCount = 0;
$resolvedCount = 0;
$ticketFilterValue = trim((string) ($customer['phone'] ?? '')) !== ''
    ? $customer['phone']
    : $customer['name'];

if ($tickets && $tickets->num_rows > 0) {
    $tickets->data_seek(0);
    while ($summaryTicket = $tickets->fetch_assoc()) {
        if (in_array($summaryTicket['status'], ['Resuelto', 'Cerrado'], true)) {
            $resolvedCount++;
        } else {
            $openCount++;
        }
    }
    $tickets->data_seek(0);
}
?>

<nav class="breadcrumbs" aria-label="Navegacion">
    <a href="<?php echo e($returnUrl); ?>">Tickets</a>
    <span>/</span>
    <span>Cliente</span>
</nav>

<header class="admin-header">
    <div>
        <h2><?php echo e($customer['name']); ?></h2>
        <p>Historial de tickets asociados al cliente.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="customer_edit.php?id=<?php echo (int) $customer['id']; ?>&return=<?php echo urlencode($returnUrl); ?>" class="btn btn--secondary">Editar cliente</a>
        <a href="ticket_create.php?customer_id=<?php echo (int) $customer['id']; ?>" class="btn">Nuevo ticket</a>
        <a href="tickets.php?customer=<?php echo urlencode($ticketFilterValue); ?>" class="btn">Filtrar en tickets</a>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a tickets</a>
    </div>
</header>

<?php if ($updated): ?>
    <div class="alert alert--success" data-auto-dismiss="4000">Los datos del cliente se actualizaron correctamente.</div>
<?php endif; ?>

<div class="ticket-toolbar" style="margin-bottom: 2rem;">
    <div class="ticket-results-summary">
        <strong><?php echo $ticketCount; ?></strong> ticket<?php echo $ticketCount === 1 ? '' : 's'; ?> asociado<?php echo $ticketCount === 1 ? '' : 's'; ?>
    </div>
    <div class="filter-chips" aria-label="Resumen del cliente">
        <span class="filter-chip">Abiertos: <?php echo $openCount; ?></span>
        <span class="filter-chip">Resueltos/Cerrados: <?php echo $resolvedCount; ?></span>
    </div>
</div>

<div class="card panel-card" style="margin-bottom: 3rem;">
    <div class="ticket-section__eyebrow">Cliente</div>
    <h3 class="ticket-section__title">Datos de contacto</h3>
    <div class="ticket-detail-list">
        <div><strong>Nombre:</strong> <?php echo e($customer['name']); ?></div>
        <div><strong>Razón social:</strong> <?php echo e(($customer['business_name'] ?? '') !== '' ? $customer['business_name'] : 'No proporcionada'); ?></div>
        <div>
            <strong>Correo:</strong>
            <?php if (trim((string) ($customer['email'] ?? '')) !== ''): ?>
                <a href="mailto:<?php echo e($customer['email']); ?>"><?php echo e($customer['email']); ?></a>
            <?php else: ?>
                No proporcionado
            <?php endif; ?>
        </div>
        <div><strong>Teléfono:</strong> <?php echo e($customer['phone'] ?: 'No proporcionado'); ?></div>
        <div><strong>Registrado:</strong> <?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></div>
        <div><strong>Contexto actual:</strong> Historial consolidado del cliente para seguimiento y triage.</div>
    </div>
</div>

<div class="table-wrapper">
    <table class="table table--tickets">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Estado</th>
                <th>Canal</th>
                <th>Tipo</th>
                <th>Contexto</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tickets && $tickets->num_rows > 0): ?>
                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                    <tr>
                        <td><a href="ticket_view.php?id=<?php echo (int) $ticket['id']; ?>&return=<?php echo urlencode($returnUrl); ?>" class="ticket-primary-link">#<?php echo (int) $ticket['id']; ?></a></td>
                        <td><span class="ticket-status ticket-status--<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><span class="ticket-status__dot" aria-hidden="true"></span><?php echo e($ticket['status']); ?></span></td>
                        <td><?php echo e($ticket['origin_type'] === 'promotion' ? 'Promocion' : ($ticket['origin_type'] === 'product' ? 'Producto' : ($ticket['origin_type'] === 'admin' ? 'Admin' : 'Contacto'))); ?></td>
                        <td><?php echo e($ticket['consultation_type']); ?></td>
                        <td><?php echo e($ticket['context_label'] ?: 'Sin contexto adicional'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                        <td><a href="ticket_view.php?id=<?php echo (int) $ticket['id']; ?>&return=<?php echo urlencode($returnUrl); ?>" class="table__action">Ver ticket</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Este cliente aún no tiene tickets registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
