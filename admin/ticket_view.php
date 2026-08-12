<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$viewerUserId = current_admin_id();
$viewerRole = current_admin_role();

$allowedStatuses = ['Nuevo', 'Agendado', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$statusMessage = '';
$statusError = '';
$ticketMessageStatus = '';
$ticketMessageError = '';
$priceMessage = '';
$priceError = '';
$assigneeMessage = '';
$assigneeError = '';
$serviceMessage = '';
$serviceError = '';
$observationMessage = '';
$observationError = '';
$observationDraft = '';
$ticketMessageDraft = trim((string) ($_POST['ticket_message'] ?? ''));
$priceDraft = trim((string) ($_POST['price_amount'] ?? ''));
$assignedUserDraft = (int) ($_POST['assigned_user_id'] ?? 0);
$serviceDateDraft = trim((string) ($_POST['service_date'] ?? ''));
$serviceStartDraft = trim((string) ($_POST['service_start_time'] ?? ''));
$serviceEndDraft = trim((string) ($_POST['service_end_time'] ?? ''));
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
    $editableTicket = $ticketId > 0 ? getTicketById($conn, $ticketId) : null;

    if (!$editableTicket || !can_admin_user_access_ticket($editableTicket, $viewerUserId, $viewerRole)) {
        $statusError = 'No tienes permisos para modificar este ticket.';
    } elseif ($action === 'update_message') {
        $adminUserId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
        $messageUpdate = update_ticket_message($conn, $ticketId, $ticketMessageDraft, $adminUserId);

        if ($messageUpdate['ok']) {
            $ticketMessageStatus = 'El mensaje del ticket se actualizó correctamente.';
            $ticketMessageDraft = '';
        } else {
            $ticketMessageError = $messageUpdate['error'] ?: 'No se pudo actualizar el mensaje del ticket.';
        }
    } elseif ($action === 'add_observation') {
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
    } elseif ($action === 'update_price') {
        $adminUserId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
        $priceUpdate = update_ticket_price($conn, $ticketId, $priceDraft, $adminUserId);

        if ($priceUpdate['ok']) {
            $priceMessage = 'El precio del ticket se actualizó correctamente.';
            $priceDraft = '';
        } else {
            $priceError = $priceUpdate['error'] ?: 'No se pudo actualizar el precio del ticket.';
        }
    } elseif ($action === 'update_assignee') {
        if ($viewerRole !== 'admin') {
            $assigneeError = 'No tienes permisos para reasignar tickets.';
        } else {
            $adminUserId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
            $assigneeUpdate = update_ticket_assignee($conn, $ticketId, $assignedUserDraft, $adminUserId);

            if ($assigneeUpdate['ok']) {
                $assigneeMessage = 'El responsable del ticket se actualizó correctamente.';
            } else {
                $assigneeError = $assigneeUpdate['error'] ?: 'No se pudo actualizar el responsable del ticket.';
            }
        }
    } elseif ($action === 'update_service_schedule') {
        $serviceUpdate = update_ticket_service_schedule($conn, $ticketId, $serviceDateDraft, $serviceStartDraft, $serviceEndDraft);

        if ($serviceUpdate['ok']) {
            $serviceMessage = 'La ejecución del servicio se actualizó correctamente.';
            $serviceDateDraft = '';
            $serviceStartDraft = '';
            $serviceEndDraft = '';
        } else {
            $serviceError = $serviceUpdate['error'] ?: 'No se pudo actualizar la ejecución del servicio.';
        }
    } else {
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($ticketId > 0 && in_array($status, $allowedStatuses, true)) {
            $existingTicket = $editableTicket;
            $programmedDateValidation = validate_ticket_programmed_date_requirement($status, $existingTicket['service_date'] ?? null);

            if (!$programmedDateValidation['ok']) {
                $statusError = $programmedDateValidation['error'];
            } elseif (update_ticket_status($conn, $ticketId, $status)) {
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
$assignableUsers = getAdminUsers($conn, true);

if (!$ticket || !can_admin_user_access_ticket($ticket, $viewerUserId, $viewerRole)) {
    ?>
    <header class="admin-header">
        <h2>Ticket no encontrado</h2>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a tickets</a>
    </header>
    <div class="card panel-card">
        <p>El ticket solicitado no existe, ya no está disponible o no tienes permisos para verlo.</p>
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
        <a href="ticket_service_order_pdf.php?id=<?php echo (int) $ticket['id']; ?>&return=<?php echo urlencode($returnUrl); ?>" class="btn">Exportar PDF</a>
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
                <div>
                    <strong>Responsable:</strong>
                    <?php
                    $assignedDisplayLabel = trim((string) ($ticket['assigned_display_name'] ?? '')) !== ''
                        ? $ticket['assigned_display_name']
                        : trim((string) ($ticket['assigned_username'] ?? ''));
                    ?>
                    <?php if ($ticket['assigned_user_id'] && $assignedDisplayLabel !== ''): ?>
                        <span><?php echo e($assignedDisplayLabel); ?></span>
                    <?php else: ?>
                        <span class="ticket-muted">Sin responsable asignado</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Última asignación:</strong>
                    <?php
                    $assignedByLabel = trim((string) ($ticket['assigned_by_display_name'] ?? '')) !== ''
                        ? $ticket['assigned_by_display_name']
                        : trim((string) ($ticket['assigned_by_username'] ?? ''));
                    ?>
                    <?php if ($ticket['assigned_user_id'] && $ticket['assigned_at']): ?>
                        <span>
                            <?php echo e($assignedByLabel !== '' ? $assignedByLabel : 'Administrador'); ?>
                            · <?php echo date('d/m/Y H:i', strtotime($ticket['assigned_at'])); ?>
                        </span>
                    <?php else: ?>
                        <span class="ticket-muted">Sin asignación registrada</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Precio:</strong>
                    <span class="<?php echo $ticket['price_amount'] !== null ? 'ticket-price' : 'ticket-muted'; ?>">
                        <?php echo e(format_ticket_price($ticket['price_amount'], $ticket['price_currency'] ?? 'MXN')); ?>
                    </span>
                </div>
                <div><strong>Fecha programada:</strong> <?php echo e(format_ticket_service_date($ticket['service_date'] ?? null)); ?></div>
                <div><strong>Hora de inicio:</strong> <?php echo e(format_ticket_service_time($ticket['service_start_time'] ?? null)); ?></div>
                <div><strong>Hora de finalización:</strong> <?php echo e(format_ticket_service_time($ticket['service_end_time'] ?? null)); ?></div>
                <div><strong>Duración:</strong> <?php echo e(format_ticket_service_duration($ticket['service_start_time'] ?? null, $ticket['service_end_time'] ?? null)); ?></div>
                <div>
                    <strong>Última actualización de precio:</strong>
                    <?php if ($ticket['price_amount'] !== null && $ticket['price_updated_at']): ?>
                        <?php
                        $priceEditorLabel = trim((string) ($ticket['price_updated_by_username'] ?? '')) !== ''
                            ? $ticket['price_updated_by_username']
                            : ($ticket['price_updated_by'] ? 'Admin #' . (int) $ticket['price_updated_by'] : 'Administrador');
                        ?>
                        <span><?php echo e($priceEditorLabel); ?> · <?php echo date('d/m/Y H:i', strtotime($ticket['price_updated_at'])); ?></span>
                    <?php else: ?>
                        <span class="ticket-muted">Sin precio asignado</span>
                    <?php endif; ?>
                </div>
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
            <p class="ticket-message__content"><?php echo nl2br(e($ticket['message'])); ?></p>

            <?php
            $messageEditorLabel = trim((string) ($ticket['message_updated_by_display_name'] ?? '')) !== ''
                ? $ticket['message_updated_by_display_name']
                : trim((string) ($ticket['message_updated_by_username'] ?? ''));
            ?>
            <?php if ($ticket['message_updated_at']): ?>
                <p class="ticket-message__meta">
                    Última edición:
                    <strong><?php echo e($messageEditorLabel !== '' ? $messageEditorLabel : ($ticket['message_updated_by'] ? 'Admin #' . (int) $ticket['message_updated_by'] : 'Administrador')); ?></strong>
                    ·
                    <time datetime="<?php echo e($ticket['message_updated_at']); ?>">
                        <?php echo date('d/m/Y H:i', strtotime($ticket['message_updated_at'])); ?>
                    </time>
                </p>
            <?php endif; ?>

            <?php if ($ticketMessageStatus): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($ticketMessageStatus); ?></div>
            <?php endif; ?>

            <?php if ($ticketMessageError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($ticketMessageError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-message-form">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="update_message">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="ticket_message">Editar mensaje principal</label>
                    <textarea
                        id="ticket_message"
                        name="ticket_message"
                        class="admin-form__input admin-form__input--textarea"
                        rows="6"
                        required
                    ><?php echo e($ticketMessageDraft !== '' ? $ticketMessageDraft : $ticket['message']); ?></textarea>
                </div>
                <button type="submit" class="btn ticket-message-form__submit">Guardar mensaje</button>
            </form>
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

            <?php if ($priceMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($priceMessage); ?></div>
            <?php endif; ?>

            <?php if ($priceError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($priceError); ?></div>
            <?php endif; ?>

            <?php if ($assigneeMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($assigneeMessage); ?></div>
            <?php endif; ?>

            <?php if ($assigneeError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($assigneeError); ?></div>
            <?php endif; ?>

            <?php if ($serviceMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($serviceMessage); ?></div>
            <?php endif; ?>

            <?php if ($serviceError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($serviceError); ?></div>
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

            <form method="POST" class="admin-form ticket-price-form">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="update_price">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="price_amount">Asignar / modificar precio (MXN)</label>
                    <input
                        type="text"
                        id="price_amount"
                        name="price_amount"
                        class="admin-form__input"
                        inputmode="decimal"
                        value="<?php echo e($priceDraft !== '' ? $priceDraft : ($ticket['price_amount'] !== null ? number_format((float) $ticket['price_amount'], 2, '.', '') : '')); ?>"
                        placeholder="0.00"
                    >
                </div>
                <button type="submit" class="btn">Guardar precio</button>
            </form>

            <?php if ($viewerRole === 'admin'): ?>
                <form method="POST" class="admin-form ticket-price-form">
                    <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                    <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                    <input type="hidden" name="ticket_action" value="update_assignee">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="assigned_user_id">Asignar / cambiar responsable</label>
                        <select id="assigned_user_id" name="assigned_user_id" class="admin-form__input">
                            <option value="">Selecciona un responsable</option>
                            <?php if ($assignableUsers): ?>
                                <?php while ($assignableUser = $assignableUsers->fetch_assoc()): ?>
                                    <?php
                                    $assignableUserId = (int) $assignableUser['id'];
                                    $assignableLabel = getAdminUserDisplayLabel($assignableUser);
                                    $selectedAssigneeId = $assignedUserDraft > 0 ? $assignedUserDraft : (int) ($ticket['assigned_user_id'] ?? 0);
                                    ?>
                                    <option value="<?php echo $assignableUserId; ?>" <?php echo $selectedAssigneeId === $assignableUserId ? 'selected' : ''; ?>>
                                        <?php echo e($assignableLabel); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Guardar responsable</button>
                </form>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-price-form">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="update_service_schedule">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="service_date">Fecha programada</label>
                    <input
                        type="date"
                        id="service_date"
                        name="service_date"
                        class="admin-form__input"
                        value="<?php echo e($serviceDateDraft !== '' ? $serviceDateDraft : (string) ($ticket['service_date'] ?? '')); ?>"
                    >
                </div>
                <div class="admin-form__row ticket-service-schedule-row">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="service_start_time">Hora de inicio</label>
                        <input
                            type="time"
                            id="service_start_time"
                            name="service_start_time"
                            class="admin-form__input"
                            value="<?php echo e($serviceStartDraft !== '' ? $serviceStartDraft : substr((string) ($ticket['service_start_time'] ?? ''), 0, 5)); ?>"
                        >
                    </div>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="service_end_time">Hora de finalización</label>
                        <input
                            type="time"
                            id="service_end_time"
                            name="service_end_time"
                            class="admin-form__input"
                            value="<?php echo e($serviceEndDraft !== '' ? $serviceEndDraft : substr((string) ($ticket['service_end_time'] ?? ''), 0, 5)); ?>"
                        >
                    </div>
                </div>
                <div class="ticket-service-schedule-summary">
                    <label class="admin-form__label" for="service_duration_preview">Duración calculada</label>
                    <input
                        type="text"
                        id="service_duration_preview"
                        class="admin-form__input ticket-service-schedule-summary__input"
                        value="<?php echo e(format_ticket_service_duration(
                            $serviceStartDraft !== '' ? $serviceStartDraft : ($ticket['service_start_time'] ?? ''),
                            $serviceEndDraft !== '' ? $serviceEndDraft : ($ticket['service_end_time'] ?? '')
                        )); ?>"
                        readonly
                    >
                </div>
                <button type="submit" class="btn">Guardar programación</button>
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
