<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | RCN Sistemas</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="logout.php" class="sidebar__link" style="margin-top: auto; color: #ff6b6b;">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </aside>
        <main class="admin-main">
