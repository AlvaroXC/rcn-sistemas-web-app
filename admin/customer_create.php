<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$message = '';
$error = '';
$form = [
    'name' => trim((string) ($_POST['name'] ?? '')),
    'business_name' => trim((string) ($_POST['business_name'] ?? '')),
    'customer_type' => normalize_customer_type($_POST['customer_type'] ?? 'persona_fisica'),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $validationError = validate_customer_payload($form['name'], $form['phone'], $form['business_name'], $form['customer_type']);

    if ($validationError !== '') {
        $error = $validationError;
    } elseif (!is_valid_optional_email($form['email'])) {
        $error = 'El correo electrónico no es válido.';
    } elseif (find_customer_by_phone($conn, $form['phone'])) {
        $error = 'Ya existe un cliente con ese número de teléfono. Revisa el registro actual o edítalo.';
    } else {
        $customer = create_or_update_customer($conn, $form['name'], $form['email'], $form['phone'], $form['business_name'], $form['customer_type']);
        if ($customer) {
            redirect_to('customer_view.php?id=' . (int) $customer['id'] . '&return=' . urlencode('customers.php'));
        }

        $error = 'No se pudo crear el cliente en este momento.';
    }
}
?>

<header class="admin-header">
    <div>
        <h2>Nuevo cliente</h2>
        <p>Registra un cliente manualmente. El teléfono es obligatorio y el correo es opcional.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="customers.php" class="btn btn--secondary">Volver a clientes</a>
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
                <label class="admin-form__label" for="name">Nombre de contacto</label>
                <input type="text" id="name" name="name" class="admin-form__input" required value="<?php echo e($form['name']); ?>">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="customer_type">Tipo de persona</label>
                <select id="customer_type" name="customer_type" class="admin-form__input">
                    <option value="persona_fisica" <?php echo $form['customer_type'] === 'persona_fisica' ? 'selected' : ''; ?>>Persona física</option>
                    <option value="persona_moral" <?php echo $form['customer_type'] === 'persona_moral' ? 'selected' : ''; ?>>Persona moral</option>
                </select>
            </div>
        </div>
        <div class="admin-form__row">
            <div class="admin-form__group">
                <label class="admin-form__label" for="business_name">Razón social</label>
                <input type="text" id="business_name" name="business_name" class="admin-form__input" value="<?php echo e($form['business_name']); ?>">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="admin-form__input" value="<?php echo e($form['email']); ?>">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label" for="phone">Teléfono</label>
                <input type="text" id="phone" name="phone" class="admin-form__input" required value="<?php echo e($form['phone']); ?>">
            </div>
        </div>
        <button type="submit" class="btn">Guardar cliente</button>
    </form>
</div>

</main>
</div>
</body>
</html>
