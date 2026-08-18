<?php
require_once __DIR__ . '/../../includes/security.php';

start_secure_session(true);
$currentAdminIsSupervisor = current_admin_is_supervisor();
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
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <h1 class="sidebar__logo">Admin<span>RCN</span></h1>
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
