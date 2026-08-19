<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$viewerUserId = current_admin_id();
$notifications = get_ticket_assignment_notifications($conn, $viewerUserId, 50);
$unreadCount = get_unread_ticket_assignment_notification_count($conn, $viewerUserId);
?>

<header class="admin-header">
    <div>
        <h2>Notificaciones</h2>
        <p>Consulta las asignaciones y reasignaciones de tickets dirigidas a tu cuenta.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="tickets.php" class="btn btn--secondary">Ver tickets</a>
    </div>
</header>

<div class="card panel-card" style="margin-bottom: 3rem;">
    <div class="notification-summary">
        <strong><?php echo (int) $unreadCount; ?></strong>
        <span>notificacion<?php echo $unreadCount === 1 ? '' : 'es'; ?> sin leer</span>
    </div>
</div>

<div class="card panel-card">
    <?php if ($notifications && $notifications->num_rows > 0): ?>
        <div class="notification-list">
            <?php while ($notification = $notifications->fetch_assoc()): ?>
                <?php
                $notificationIsRead = (int) ($notification['is_read'] ?? 0) === 1;
                $notificationActor = trim((string) ($notification['actor_display_name'] ?? '')) !== ''
                    ? trim((string) $notification['actor_display_name'])
                    : trim((string) ($notification['actor_username'] ?? ''));
                $ticketUrl = 'ticket_view.php?id=' . (int) $notification['ticket_id'] . '&return=' . urlencode('notifications.php');
                ?>
                <article class="notification-item <?php echo $notificationIsRead ? 'notification-item--read' : 'notification-item--unread'; ?>">
                    <div class="notification-item__body">
                        <div class="notification-item__header">
                            <h3><?php echo e($notification['title']); ?></h3>
                            <?php if (!$notificationIsRead): ?>
                                <span class="sidebar__badge">Nueva</span>
                            <?php endif; ?>
                        </div>
                        <p><?php echo e($notification['message']); ?></p>
                        <div class="ticket-meta">
                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                            <?php if ($notificationActor !== ''): ?>
                                · Actor: <?php echo e($notificationActor); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notification-item__actions">
                        <a href="<?php echo e($ticketUrl); ?>" class="btn btn--secondary">Abrir ticket</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="ticket-observation-empty">
            No tienes notificaciones de asignación por ahora.
        </div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>
