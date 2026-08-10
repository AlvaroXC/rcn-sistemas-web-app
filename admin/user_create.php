<?php
require_once '../includes/security.php';
require_admin_role();
require_once '../includes/db.php';
include 'includes/admin_header.php';

$error = '';
$form = [
    'username' => trim((string) ($_POST['username'] ?? '')),
    'display_name' => trim((string) ($_POST['display_name'] ?? '')),
    'role' => normalize_admin_role($_POST['role'] ?? 'agent'),
    'active' => (int) ($_POST['active'] ?? 1) === 1 ? 1 : 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $password = (string) ($_POST['password'] ?? '');
    if ($form['username'] === '' || $password === '') {
        $error = 'Usuario y contraseña son obligatorios.';
    } else {
        $createResult = create_admin_user($conn, $form['username'], $password, $form['display_name'], $form['active'], $form['role']);
        if ($createResult['ok'] && !empty($createResult['user'])) {
            redirect_to('users.php');
        }
        $error = $createResult['error'] ?: 'No se pudo crear el usuario administrativo.';
    }
}
?>

<header class="admin-header">
    <div>
        <h2>Nuevo usuario administrativo</h2>
        <p>Crea una cuenta con acceso al panel y elegible como responsable de tickets.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="users.php" class="btn btn--secondary">Volver a usuarios</a>
    </div>
</header>

<div class="card panel-card">
    <?php if ($error): ?>
        <div class="alert alert--error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="admin-form">
        <?php echo csrf_field(); ?>
        <div class="admin-form__row">
            <div class="admin-form__group">
                <label class="admin-form__label" for="display_name">Nombre visible</label>
                <input type="text" id="display_name" name="display_name" class="admin-form__input" value="<?php echo e($form['display_name']); ?>">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="username">Usuario</label>
                <input type="text" id="username" name="username" class="admin-form__input" required value="<?php echo e($form['username']); ?>">
            </div>
        </div>
        <div class="admin-form__row">
            <div class="admin-form__group">
                <label class="admin-form__label" for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="admin-form__input" required>
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="role">Rol</label>
                <select id="role" name="role" class="admin-form__input">
                    <option value="admin" <?php echo $form['role'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="agent" <?php echo $form['role'] === 'agent' ? 'selected' : ''; ?>>Operativo</option>
                </select>
            </div>
        </div>
        <div class="admin-form__row">
            <div class="admin-form__group">
                <label class="admin-form__label" for="active">Estado</label>
                <select id="active" name="active" class="admin-form__input">
                    <option value="1" <?php echo $form['active'] === 1 ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo $form['active'] === 0 ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">Guardar usuario</button>
    </form>
</div>

</main>
</div>
</body>
</html>
