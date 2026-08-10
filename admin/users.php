<?php
require_once '../includes/security.php';
require_admin_role();
include 'includes/admin_header.php';
require_once '../includes/db.php';

$users = getAdminUsers($conn, false);
?>

<header class="admin-header">
    <div>
        <h2>Usuarios Administrativos</h2>
        <p>Gestiona las cuentas que pueden iniciar sesión y ser usadas como responsables en tickets.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="user_create.php" class="btn">Nuevo usuario</a>
    </div>
</header>

<div class="table-wrapper">
    <table class="table table--tickets">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users && $users->num_rows > 0): ?>
                <?php while ($adminUser = $users->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo e(getAdminUserDisplayLabel($adminUser)); ?></strong></td>
                        <td><?php echo e($adminUser['username']); ?></td>
                        <td><?php echo e(($adminUser['role'] ?? 'admin') === 'admin' ? 'Administrador' : 'Operativo'); ?></td>
                        <td>
                            <?php if ((int) ($adminUser['active'] ?? 0) === 1): ?>
                                <span class="ticket-assignee">Activo</span>
                            <?php else: ?>
                                <span class="ticket-muted">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($adminUser['created_at'])); ?></td>
                        <td class="table__actions">
                            <a href="user_edit.php?id=<?php echo (int) $adminUser['id']; ?>" class="table__action">Editar usuario</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No hay usuarios administrativos registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
