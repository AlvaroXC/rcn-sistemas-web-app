<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$allowedStatuses = ['Nuevo', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$statusMessage = '';
$statusError = '';
$observationMessage = '';
$observationError = '';
$observationDraft = '';
$returnUrl = trim((string) ($_GET['return'] ?? $_POST['return'] ?? 'tickets.php'));

if ($returnUrl === '' || strpos($returnUrl, 'http') === 0) {
    $returnUrl = 'tickets.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $action = trim((string) ($_POST['ticket_action'] ?? 'update_status'));

    if ($action === 'add_observation') {
        $observationContent = trim((string) ($_POST['observation_content'] ?? ''));
        $observationDraft = $observationContent;
        $adminUserId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;

        if ($ticketId <= 0) {
            $observationError = 'No se pudo identificar el ticket.';
        } elseif ($observationContent === '') {
            $observationError = 'La observación es obligatoria.';
        } elseif (create_ticket_observation($conn, $ticketId, $observationContent, $adminUserId)) {
            $observationMessage = 'La observación se guardó correctamente.';
            $observationDraft = '';
        } else {
            $observationError = 'No se pudo guardar la observación.';
        }
    } else {
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($ticketId > 0 && in_array($status, $allowedStatuses, true)) {
            if (update_ticket_status($conn, $ticketId, $status)) {
                $statusMessage = 'El estado del ticket se actualizó correctamente.';
            } else {
                $statusError = 'No se pudo actualizar el estado del ticket.';
            }
        } else {
            $statusError = 'Selecciona un estado válido.';
        }
    }
}

$ticket = $ticketId > 0 ? getTicketById($conn, $ticketId) : null;
$observations = $ticketId > 0 ? getTicketObservations($conn, $ticketId) : false;

if (!$ticket) {
    ?>
    <header class="admin-header">
        <h2>Ticket no encontrado</h2>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a tickets</a>
    </header>
    <div class="card panel-card">
        <p>El ticket solicitado no existe o ya no está disponible.</p>
    </div>
    </main>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<nav class="breadcrumbs" aria-label="Navegacion">
    <a href="<?php echo e($returnUrl); ?>">Tickets</a>
    <span>/</span>
    <span>Ticket #<?php echo (int) $ticket['id']; ?></span>
</nav>

<header class="admin-header">
    <div>
        <h2>Ticket #<?php echo (int) $ticket['id']; ?></h2>
        <p>Creado el <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
    </div>
    <div class="ticket-header-actions">
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a tickets</a>
        <a href="customer_view.php?id=<?php echo (int) $ticket['customer_id']; ?>&return=<?php echo urlencode($returnUrl); ?>" class="btn">Ver cliente</a>
    </div>
</header>

<div class="ticket-detail-grid">
    <div class="card panel-card">
        <section class="ticket-section">
            <div class="ticket-section__eyebrow">Resumen</div>
            <h3 class="ticket-section__title">Detalles del ticket</h3>
            <div class="ticket-detail-list">
                <div><strong>Tipo de consulta:</strong> <?php echo e($ticket['consultation_type']); ?></div>
                <div><strong>Canal:</strong> <?php echo e(ticket_origin_label($ticket['origin_type'])); ?></div>
                <div><strong>Contexto:</strong> <?php echo e($ticket['context_label'] ?: 'Sin contexto adicional'); ?></div>
                <div><strong>Asunto:</strong> <?php echo e($ticket['subject'] ?: 'Sin asunto'); ?></div>
                <div><strong>Adjunto:</strong>
                    <?php if ($ticket['attachment_path']): ?>
                        <a href="../<?php echo e($ticket['attachment_path']); ?>" target="_blank" rel="noopener" class="ticket-attachment-link">
                            <i class="fas fa-paperclip" aria-hidden="true"></i>
                            <span>Ver archivo</span>
                        </a>
                    <?php else: ?>
                        <span class="ticket-muted">Sin adjunto</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="ticket-section">
            <div class="ticket-section__eyebrow">Cliente</div>
            <h3 class="ticket-section__title">Datos de contacto</h3>
            <div class="ticket-detail-list">
                <div><strong>Nombre:</strong> <?php echo e($ticket['customer_name']); ?></div>
                <div><strong>Razón social:</strong> <?php echo e(($ticket['customer_business_name'] ?? '') !== '' ? $ticket['customer_business_name'] : 'No proporcionada'); ?></div>
                <div>
                    <strong>Correo:</strong>
                    <?php if (trim((string) ($ticket['customer_email'] ?? '')) !== ''): ?>
                        <a href="mailto:<?php echo e($ticket['customer_email']); ?>"><?php echo e($ticket['customer_email']); ?></a>
                    <?php else: ?>
                        No proporcionado
                    <?php endif; ?>
                </div>
                <div><strong>Teléfono:</strong> <?php echo e($ticket['customer_phone'] ?: 'No proporcionado'); ?></div>
            </div>
        </section>

        <section class="ticket-section ticket-message">
            <div class="ticket-section__eyebrow">Mensaje</div>
            <h3 class="ticket-section__title">Solicitud del cliente</h3>
            <p><?php echo nl2br(e($ticket['message'])); ?></p>
        </section>

        <section class="ticket-section ticket-observations">
            <div class="ticket-section__eyebrow">Interno</div>
            <h3 class="ticket-section__title">Observaciones</h3>
            <p class="ticket-observations__intro">Documenta seguimiento interno del caso. Estas observaciones solo se muestran en el panel administrativo.</p>

            <?php if ($observationMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($observationMessage); ?></div>
            <?php endif; ?>

            <?php if ($observationError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($observationError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-observation-form">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="add_observation">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="observation_content">Nueva observación</label>
                    <textarea
                        id="observation_content"
                        name="observation_content"
                        class="admin-form__input admin-form__input--textarea"
                        rows="4"
                        placeholder="Escribe aquí comentarios internos, próximos pasos o contexto adicional."
                    ><?php echo e($observationDraft); ?></textarea>
                </div>
                <button type="submit" class="btn ticket-observation-form__submit">Guardar observación</button>
            </form>

            <div class="ticket-observation-history">
                <h4 class="ticket-observation-history__title">Historial</h4>

                <?php if ($observations && $observations->num_rows > 0): ?>
                    <div class="ticket-observation-list">
                        <?php while ($observation = $observations->fetch_assoc()): ?>
                            <?php
                            $authorLabel = trim((string) ($observation['admin_username'] ?? '')) !== ''
                                ? $observation['admin_username']
                                : ($observation['admin_user_id'] ? 'Admin #' . (int) $observation['admin_user_id'] : 'Administrador');
                            ?>
                            <article class="ticket-observation-item">
                                <div class="ticket-observation-item__meta">
                                    <span class="ticket-observation-item__author"><?php echo e($authorLabel); ?></span>
                                    <time datetime="<?php echo e($observation['created_at']); ?>">
                                        <?php echo date('d/m/Y H:i', strtotime($observation['created_at'])); ?>
                                    </time>
                                </div>
                                <p class="ticket-observation-item__content"><?php echo nl2br(e($observation['content'])); ?></p>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="ticket-observation-empty">
                        Aún no hay observaciones para este ticket.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="card panel-card ticket-status-card">
        <div class="ticket-status-panel">
            <div>
                <div class="ticket-section__eyebrow">Seguimiento</div>
                <h3 style="margin-bottom: 1rem;">Estado actual</h3>
                <div class="ticket-status-panel__current">
                    <span class="ticket-status ticket-status--<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><span class="ticket-status__dot" aria-hidden="true"></span><?php echo e($ticket['status']); ?></span>
                </div>
                <p class="ticket-status-panel__hint">Actualiza el estado para reflejar el avance operativo del ticket.</p>
            </div>

            <?php if ($statusMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($statusMessage); ?></div>
            <?php endif; ?>

            <?php if ($statusError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($statusError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="update_status">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="status">Actualizar estado</label>
                    <select id="status" name="status" class="admin-form__input">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $ticket['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Guardar estado</button>
            </form>
        </div>
    </div>
</div>

</main>
</div>
<?php $ticketScriptVersion = @filemtime(__DIR__ . '/../js/main.js') ?: time(); ?>
<script src="../js/main.js?v=<?php echo $ticketScriptVersion; ?>"></script>
</body>
</html>
