<?php
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';

start_secure_session(true);
$currentAdminIsSupervisor = current_admin_is_supervisor();
$currentAdminId = current_admin_id();
$currentAdminLabel = current_admin_user_label();
$currentAdminRole = current_admin_role();
$currentAdminNotificationCount = get_unread_ticket_assignment_notification_count($conn, $currentAdminId);
$currentAdminLatestNotificationId = get_latest_ticket_assignment_notification_id($conn, $currentAdminId);
$currentAdminNotificationStreamUrl = 'notifications_stream.php?last_id=' . (int) $currentAdminLatestNotificationId;
$currentAdminRoleLabel = $currentAdminRole === 'admin' ? 'Administrador' : 'Operador';
$currentAdminInitial = strtoupper(substr($currentAdminLabel !== '' ? $currentAdminLabel : $currentAdminRoleLabel, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | RCN Sistemas</title>
    <?php $adminStylesVersion = @filemtime(__DIR__ . '/../../css/styles.css') ?: time(); ?>
    <?php $adminScriptVersion = @filemtime(__DIR__ . '/../../js/main.js') ?: time(); ?>
    <link rel="stylesheet" href="../css/styles.css?v=<?php echo $adminStylesVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/main.js?v=<?php echo $adminScriptVersion; ?>" defer></script>
</head>
<body
    data-notification-stream-url="<?php echo htmlspecialchars($currentAdminNotificationStreamUrl, ENT_QUOTES, 'UTF-8'); ?>"
    data-notification-login-url="login.php"
    data-notification-list-url="notifications.php"
    data-notification-unread-count="<?php echo (int) $currentAdminNotificationCount; ?>"
    data-notification-sound-url="../src/notification.mp3"
>
    <div class="admin-layout">
        <aside class="sidebar">
            <h1 class="sidebar__logo">Admin<span>RCN</span></h1>
            <section class="sidebar__session" aria-label="Sesión activa">
                <div class="sidebar__session-avatar" aria-hidden="true">
                    <?php echo htmlspecialchars($currentAdminInitial, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="sidebar__session-body">
                    <p class="sidebar__session-kicker">Sesión activa</p>
                    <p class="sidebar__session-user"><?php echo htmlspecialchars($currentAdminLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="sidebar__session-role"><?php echo htmlspecialchars($currentAdminRoleLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </section>
            <nav class="sidebar__nav">
                <a href="index.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'sidebar__link--active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="products.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'sidebar__link--active' : ''; ?>">
                    <i class="fas fa-box"></i> Productos
                </a>
                <a href="promotions.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'sidebar__link--active' : ''; ?>">
                    <i class="fas fa-tag"></i> Promociones
                </a>
                <a href="tickets.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' || basename($_SERVER['PHP_SELF']) == 'ticket_view.php' || basename($_SERVER['PHP_SELF']) == 'ticket_create.php' ? 'sidebar__link--active' : ''; ?>">
                    <i class="fas fa-life-ring"></i> Tickets
                </a>
                <a href="customers.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' || basename($_SERVER['PHP_SELF']) == 'customer_create.php' || basename($_SERVER['PHP_SELF']) == 'customer_view.php' || basename($_SERVER['PHP_SELF']) == 'customer_edit.php' ? 'sidebar__link--active' : ''; ?>">
                    <i class="fas fa-address-book"></i> Clientes
                </a>
                <a href="notifications.php" class="sidebar__link sidebar__link--with-badge <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'sidebar__link--active' : ''; ?>">
                    <span class="sidebar__link-content">
                        <i class="fas fa-bell"></i> Notificaciones
                    </span>
                    <span class="sidebar__badge" data-notification-badge<?php echo $currentAdminNotificationCount > 0 ? '' : ' hidden'; ?>>
                        <?php echo $currentAdminNotificationCount > 0 ? (int) $currentAdminNotificationCount : ''; ?>
                    </span>
                </a>
                <?php if ($currentAdminIsSupervisor): ?>
                    <a href="users.php" class="sidebar__link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'user_create.php' || basename($_SERVER['PHP_SELF']) == 'user_edit.php' ? 'sidebar__link--active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> Usuarios
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="sidebar__link" style="margin-top: auto; color: #ff6b6b;">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </aside>
        <main class="admin-main">
