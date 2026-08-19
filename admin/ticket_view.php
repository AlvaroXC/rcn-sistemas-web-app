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
$returnUrl = normalize_return_url($_GET['return'] ?? $_POST['return'] ?? 'tickets.php', 'tickets.php');

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
        $adminUserId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
        $assigneeUpdate = update_ticket_assignee($conn, $ticketId, $assignedUserDraft, $adminUserId, $viewerRole);

        if ($assigneeUpdate['ok']) {
            if ($viewerRole !== 'admin') {
                set_flash_message(
                    'ticket_notice',
                    'Transferiste el ticket correctamente. Ya no aparecerá en tu bandeja porque quedó asignado a otro responsable.',
                    'success'
                );
                redirect_to($returnUrl);
            } else {
                $assigneeMessage = 'El responsable del ticket se actualizó correctamente.';
            }
        } else {
            $assigneeError = $assigneeUpdate['error'] ?: 'No se pudo actualizar el responsable del ticket.';
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
            $statusUpdate = update_ticket_status($conn, $ticketId, $status);

            if ($statusUpdate['ok']) {
                $statusMessage = 'El estado del ticket se actualizó correctamente.';
            } else {
                $statusError = $statusUpdate['error'] ?: 'No se pudo actualizar el estado del ticket.';
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

mark_ticket_assignment_notifications_as_read($conn, $viewerUserId, (int) $ticket['id']);

$customerType = normalize_customer_type($ticket['customer_type'] ?? 'persona_fisica');
$customerTypeLabel = $customerType === 'persona_moral' ? 'Persona moral' : 'Persona fisica';
$assignedDisplayLabel = trim((string) ($ticket['assigned_display_name'] ?? '')) !== ''
    ? $ticket['assigned_display_name']
    : trim((string) ($ticket['assigned_username'] ?? ''));
$assignedByLabel = trim((string) ($ticket['assigned_by_display_name'] ?? '')) !== ''
    ? $ticket['assigned_by_display_name']
    : trim((string) ($ticket['assigned_by_username'] ?? ''));
$priceEditorLabel = trim((string) ($ticket['price_updated_by_username'] ?? '')) !== ''
    ? $ticket['price_updated_by_username']
    : ($ticket['price_updated_by'] ? 'Admin #' . (int) $ticket['price_updated_by'] : 'Administrador');
$messageEditorLabel = trim((string) ($ticket['message_updated_by_display_name'] ?? '')) !== ''
    ? $ticket['message_updated_by_display_name']
    : trim((string) ($ticket['message_updated_by_username'] ?? ''));
$selectedAssigneeId = $assignedUserDraft > 0 ? $assignedUserDraft : (int) ($ticket['assigned_user_id'] ?? 0);
$effectivePriceAmount = get_ticket_effective_price_amount($ticket);
$effectivePriceBaseAmount = get_ticket_effective_price_base_amount($ticket);
$hasPriceBreakdown = ticket_has_price_breakdown($ticket);
$hasAttachment = trim((string) ($ticket['attachment_path'] ?? '')) !== '';
$isClosedTicket = trim((string) ($ticket['status'] ?? '')) === 'Cerrado';
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
    <div class="ticket-detail-main">
        <section class="card panel-card ticket-section-card">
            <div class="ticket-section__eyebrow">Resumen</div>
            <h3 class="ticket-section__title">Visión general del ticket</h3>
            <div class="ticket-summary-grid">
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Tipo de consulta</span>
                    <strong class="ticket-kv__value"><?php echo e($ticket['consultation_type']); ?></strong>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Canal</span>
                    <strong class="ticket-kv__value"><?php echo e(ticket_origin_label($ticket['origin_type'])); ?></strong>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Contexto</span>
                    <span class="ticket-kv__value"><?php echo e($ticket['context_label'] ?: 'Sin contexto adicional'); ?></span>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Asunto</span>
                    <span class="ticket-kv__value"><?php echo e($ticket['subject'] ?: 'Sin asunto'); ?></span>
                </div>
            </div>
        </section>

        <section class="card panel-card ticket-section-card">
            <div class="ticket-section__eyebrow">Precio</div>
            <div class="ticket-section-card__header">
                <div>
                    <h3 class="ticket-section__title">Resumen fiscal</h3>
                    <p class="ticket-section-card__intro">El total final se calcula con base en el tipo de cliente actual.</p>
                </div>
                <?php if ($effectivePriceAmount !== null): ?>
                    <div class="ticket-price-highlight">
                        <span class="ticket-price-highlight__label">Total final</span>
                        <span class="ticket-price"><?php echo e(format_ticket_price($effectivePriceAmount, $ticket['price_currency'] ?? 'MXN')); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($hasPriceBreakdown): ?>
                <div class="ticket-summary-grid ticket-summary-grid--compact">
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Precio base</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_price($ticket['price_base_amount'], $ticket['price_currency'] ?? 'MXN')); ?></span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">IVA</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_price($ticket['price_iva_amount'], $ticket['price_currency'] ?? 'MXN')); ?></span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">ISR retenido</span>
                        <span class="ticket-kv__value">
                            <?php if ($customerType === 'persona_moral'): ?>
                                <?php echo e(format_ticket_price($ticket['price_isr_withholding_amount'], $ticket['price_currency'] ?? 'MXN')); ?>
                            <?php else: ?>
                                No aplica
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Tipo de cliente</span>
                        <span class="ticket-kv__value"><?php echo e($customerTypeLabel); ?></span>
                    </div>
                </div>
            <?php elseif ($effectivePriceAmount !== null): ?>
                <div class="ticket-empty-state">
                    <strong>Precio registrado:</strong>
                    <span class="ticket-price"><?php echo e(format_ticket_price($effectivePriceAmount, $ticket['price_currency'] ?? 'MXN')); ?></span>
                    <p>Este ticket conserva un precio heredado sin desglose fiscal almacenado.</p>
                </div>
            <?php else: ?>
                <div class="ticket-empty-state">
                    <strong>Sin precio asignado</strong>
                    <p>Cuando captures un precio base, el total final se recalculará automáticamente.</p>
                </div>
            <?php endif; ?>

            <div class="ticket-meta-note">
                <strong>Última actualización:</strong>
                <?php if ($effectivePriceAmount !== null && $ticket['price_updated_at']): ?>
                    <span><?php echo e($priceEditorLabel); ?> · <?php echo date('d/m/Y H:i', strtotime($ticket['price_updated_at'])); ?></span>
                <?php else: ?>
                    <span class="ticket-muted">Sin precio asignado</span>
                <?php endif; ?>
            </div>
        </section>

        <div class="ticket-section-split">
            <section class="card panel-card ticket-section-card">
                <div class="ticket-section__eyebrow">Asignación</div>
                <h3 class="ticket-section__title">Responsabilidad operativa</h3>
                <div class="ticket-detail-list">
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Responsable actual</span>
                        <?php if ($ticket['assigned_user_id'] && $assignedDisplayLabel !== ''): ?>
                            <span class="ticket-assignee"><?php echo e($assignedDisplayLabel); ?></span>
                        <?php else: ?>
                            <span class="ticket-muted">Sin responsable asignado</span>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Última asignación</span>
                        <?php if ($ticket['assigned_user_id'] && $ticket['assigned_at']): ?>
                            <span class="ticket-kv__value">
                                <?php echo e($assignedByLabel !== '' ? $assignedByLabel : 'Administrador'); ?>
                                · <?php echo date('d/m/Y H:i', strtotime($ticket['assigned_at'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="ticket-muted">Sin asignación registrada</span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="card panel-card ticket-section-card">
                <div class="ticket-section__eyebrow">Programación</div>
                <h3 class="ticket-section__title">Servicio programado</h3>
                <div class="ticket-detail-list">
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Fecha programada</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_service_date($ticket['service_date'] ?? null)); ?></span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Hora de inicio</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_service_time($ticket['service_start_time'] ?? null)); ?></span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Hora de finalización</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_service_time($ticket['service_end_time'] ?? null)); ?></span>
                    </div>
                    <div class="ticket-kv">
                        <span class="ticket-kv__label">Duración</span>
                        <span class="ticket-kv__value"><?php echo e(format_ticket_service_duration($ticket['service_start_time'] ?? null, $ticket['service_end_time'] ?? null)); ?></span>
                    </div>
                </div>
            </section>
        </div>

        <section class="card panel-card ticket-section-card">
            <div class="ticket-section__eyebrow">Cliente</div>
            <h3 class="ticket-section__title">Datos de contacto</h3>
            <div class="ticket-summary-grid ticket-summary-grid--compact">
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Nombre</span>
                    <span class="ticket-kv__value"><?php echo e($ticket['customer_name']); ?></span>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Tipo de cliente</span>
                    <span class="ticket-kv__value"><?php echo e($customerTypeLabel); ?></span>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Razón social</span>
                    <span class="ticket-kv__value"><?php echo e(($ticket['customer_business_name'] ?? '') !== '' ? $ticket['customer_business_name'] : 'No proporcionada'); ?></span>
                </div>
                <div class="ticket-kv">
                    <span class="ticket-kv__label">Teléfono</span>
                    <span class="ticket-kv__value"><?php echo e($ticket['customer_phone'] ?: 'No proporcionado'); ?></span>
                </div>
                <div class="ticket-kv ticket-kv--wide">
                    <span class="ticket-kv__label">Correo</span>
                    <?php if (trim((string) ($ticket['customer_email'] ?? '')) !== ''): ?>
                        <a href="mailto:<?php echo e($ticket['customer_email']); ?>" class="ticket-kv__value ticket-kv__value--link"><?php echo e($ticket['customer_email']); ?></a>
                    <?php else: ?>
                        <span class="ticket-kv__value">No proporcionado</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card panel-card ticket-section-card ticket-message">
            <div class="ticket-section__eyebrow">Mensaje</div>
            <h3 class="ticket-section__title">Solicitud del cliente</h3>
            <div class="ticket-message-display">
                <p class="ticket-message__content"><?php echo nl2br(e($ticket['message'])); ?></p>
            </div>

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
        </section>

        <section class="card panel-card ticket-section-card">
            <div class="ticket-section__eyebrow">Adjunto</div>
            <h3 class="ticket-section__title">Archivo relacionado</h3>
            <?php if ($hasAttachment): ?>
                <div class="ticket-attachment-card">
                    <p class="ticket-section-card__intro">El archivo adjunto del ticket se mantiene disponible para consulta administrativa.</p>
                    <a href="../<?php echo e($ticket['attachment_path']); ?>" target="_blank" rel="noopener" class="ticket-attachment-link">
                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                        <span>Ver archivo</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="ticket-empty-state">
                    <strong>Sin adjunto</strong>
                    <p>Este ticket no tiene archivo relacionado.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="card panel-card ticket-section-card ticket-observations">
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

    <aside class="ticket-detail-sidebar">
        <div class="card panel-card ticket-status-card">
            <div class="ticket-status-panel">
                <div>
                    <div class="ticket-section__eyebrow">Seguimiento</div>
                    <h3 class="ticket-section__title ticket-section__title--compact">Estado actual</h3>
                    <div class="ticket-status-panel__current">
                        <span class="ticket-status ticket-status--<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><span class="ticket-status__dot" aria-hidden="true"></span><?php echo e($ticket['status']); ?></span>
                    </div>
                    <p class="ticket-status-panel__hint">
                        <?php if ($isClosedTicket): ?>
                            Este ticket está cerrado y ya no admite cambios de estado.
                        <?php else: ?>
                            Actualiza el estado para reflejar el avance operativo del ticket.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($statusMessage): ?>
                    <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($statusMessage); ?></div>
                <?php endif; ?>

                <?php if ($statusError): ?>
                    <div class="alert alert--error ticket-inline-alert"><?php echo e($statusError); ?></div>
                <?php endif; ?>

                <?php if ($isClosedTicket): ?>
                    <div class="alert alert--info ticket-inline-alert">
                        El ticket permanece disponible para consulta, pero el estado `Cerrado` es final y no puede modificarse.
                    </div>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>

        <div class="card panel-card ticket-action-card">
            <div class="ticket-action-card__header">
                <div class="ticket-section__eyebrow">Acciones</div>
                <h3 class="ticket-section__title ticket-section__title--compact">Precio</h3>
            </div>

            <?php if ($priceMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($priceMessage); ?></div>
            <?php endif; ?>

            <?php if ($priceError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($priceError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-price-form ticket-price-form--first">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">
                <input type="hidden" name="ticket_action" value="update_price">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="price_amount">Asignar / modificar precio base (MXN)</label>
                    <input
                        type="text"
                        id="price_amount"
                        name="price_amount"
                        class="admin-form__input"
                        inputmode="decimal"
                        value="<?php echo e($priceDraft !== '' ? $priceDraft : ($effectivePriceBaseAmount !== null ? number_format((float) $effectivePriceBaseAmount, 2, '.', '') : '')); ?>"
                        placeholder="0.00"
                    >
                    <div class="ticket-meta">El total final se recalcula automaticamente segun el tipo de cliente actual.</div>
                </div>
                <button type="submit" class="btn">Guardar precio</button>
            </form>
        </div>

        <?php if (can_admin_user_reassign_ticket($ticket, $viewerUserId, $viewerRole)): ?>
            <div class="card panel-card ticket-action-card">
                <div class="ticket-section__eyebrow">Acciones</div>
                <h3 class="ticket-section__title ticket-section__title--compact">Responsable</h3>

                <?php if ($assigneeMessage): ?>
                    <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($assigneeMessage); ?></div>
                <?php endif; ?>

                <?php if ($assigneeError): ?>
                    <div class="alert alert--error ticket-inline-alert"><?php echo e($assigneeError); ?></div>
                <?php endif; ?>

                <form method="POST" class="admin-form ticket-price-form ticket-price-form--first">
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
                                    ?>
                                    <option value="<?php echo $assignableUserId; ?>" <?php echo $selectedAssigneeId === $assignableUserId ? 'selected' : ''; ?>>
                                        <?php echo e($assignableLabel); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <?php if ($viewerRole !== 'admin'): ?>
                            <div class="ticket-meta">Al transferir este ticket dejará de aparecer en tu bandeja y quedará disponible solo para el nuevo responsable.</div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn">Guardar responsable</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card panel-card ticket-action-card">
            <div class="ticket-action-card__header">
                <div class="ticket-section__eyebrow">Acciones</div>
                <h3 class="ticket-section__title ticket-section__title--compact">Programación</h3>
            </div>

            <?php if ($serviceMessage): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($serviceMessage); ?></div>
            <?php endif; ?>

            <?php if ($serviceError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($serviceError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-price-form ticket-price-form--first">
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

        <div class="card panel-card ticket-action-card ticket-message-edit-card">
            <div class="ticket-action-card__header">
                <div class="ticket-section__eyebrow">Acciones</div>
                <h3 class="ticket-section__title ticket-section__title--compact">Editar mensaje</h3>
            </div>

            <?php if ($ticketMessageStatus): ?>
                <div class="alert alert--success ticket-inline-alert" data-auto-dismiss="5000"><?php echo e($ticketMessageStatus); ?></div>
            <?php endif; ?>

            <?php if ($ticketMessageError): ?>
                <div class="alert alert--error ticket-inline-alert"><?php echo e($ticketMessageError); ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form ticket-message-form ticket-price-form--first">
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
        </div>
    </aside>
</div>

</main>
</div>
</body>
</html>
