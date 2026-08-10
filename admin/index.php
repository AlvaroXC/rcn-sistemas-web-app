<?php include 'includes/admin_header.php'; ?>
<?php require_once '../includes/db.php'; ?>
<?php $ticketStats = getTicketStats($conn, current_admin_id(), current_admin_role()); ?>

<header class="admin-header">
    <h2>Dashboard Overview</h2>
    <p>Bienvenido, <strong><?php echo $_SESSION['admin_user']; ?></strong></p>
</header>

<div class="stats">
    <div class="stat-card">
        <i class="fas fa-box stat-card__icon" style="color: var(--secondary);"></i>
        <div class="stat-card__value"><?php echo $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0]; ?></div>
        <p class="stat-card__label">Productos</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-tag stat-card__icon" style="color: var(--accent);"></i>
        <div class="stat-card__value"><?php echo $conn->query("SELECT COUNT(*) FROM promotions")->fetch_row()[0]; ?></div>
        <p class="stat-card__label">Promociones</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-comment-alt stat-card__icon" style="color: #6f42c1;"></i>
        <div class="stat-card__value"><?php echo $conn->query("SELECT COUNT(*) FROM testimonials")->fetch_row()[0]; ?></div>
        <p class="stat-card__label">Testimoniales</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-life-ring stat-card__icon" style="color: #d97706;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['total']; ?></div>
        <p class="stat-card__label">Tickets</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-friends stat-card__icon" style="color: #1d4ed8;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['customers']; ?></div>
        <p class="stat-card__label">Clientes</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-hourglass-half stat-card__icon" style="color: #dc2626;"></i>
        <div class="stat-card__value"><?php echo $ticketStats['open']; ?></div>
        <p class="stat-card__label">Tickets Abiertos</p>
    </div>
</div>

<div class="card" style="padding: 3rem;">
    <h3>Acciones Rápidas</h3>
    <div style="display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;">
        <a href="products.php?action=new" class="btn">Agregar Producto</a>
        <a href="promotions.php?action=new" class="btn btn--secondary">Nueva Promoción</a>
        <a href="tickets.php" class="btn btn--secondary">Ver Tickets</a>
        <a href="ticket_create.php" class="btn">Nuevo Ticket</a>
        <a href="customer_create.php" class="btn btn--secondary">Nuevo Cliente</a>
    </div>
</div>

</main>
</div>
</body>
</html>
