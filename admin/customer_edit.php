<?php
include 'includes/admin_header.php';
require_once '../includes/db.php';

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$customer = $customerId > 0 ? getCustomerById($conn, $customerId) : null;
$returnUrl = normalize_return_url($_GET['return'] ?? $_POST['return'] ?? 'customers.php', 'customers.php');

if (!$customer) {
    ?>
    <header class="admin-header">
        <h2>Cliente no encontrado</h2>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a clientes</a>
    </header>
    <div class="card panel-card">
        <p>El cliente solicitado no existe o ya no está disponible.</p>
    </div>
    </main>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$error = '';
$form = [
    'name' => trim((string) ($_POST['name'] ?? $customer['name'] ?? '')),
    'business_name' => trim((string) ($_POST['business_name'] ?? $customer['business_name'] ?? '')),
    'customer_type' => normalize_customer_type($_POST['customer_type'] ?? ($customer['customer_type'] ?? 'persona_fisica')),
    'email' => trim((string) ($_POST['email'] ?? $customer['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? $customer['phone'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $validationError = validate_customer_payload($form['name'], $form['phone'], $form['business_name'], $form['customer_type']);

    if ($validationError !== '') {
        $error = $validationError;
    } elseif (!is_valid_optional_email($form['email'])) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $updateResult = update_customer_by_id(
            $conn,
            $customerId,
            $form['name'],
            $form['email'],
            $form['phone'],
            $form['business_name'],
            $form['customer_type']
        );

        if ($updateResult['ok'] && !empty($updateResult['customer'])) {
            set_flash_message('customer_notice', 'Los datos del cliente se actualizaron correctamente.', 'success');
            redirect_to('customer_view.php?id=' . (int) $customerId . '&return=' . urlencode($returnUrl));
        }

        $error = $updateResult['error'] ?: 'No se pudo actualizar el cliente en este momento.';
    }
}
?>

<header class="admin-header">
    <div>
        <h2>Editar cliente</h2>
        <p>Actualiza los datos del cliente. El teléfono es obligatorio y el correo es opcional.</p>
    </div>
    <div class="ticket-header-actions">
        <a href="customer_view.php?id=<?php echo (int) $customerId; ?>&return=<?php echo urlencode($returnUrl); ?>" class="btn">Ver cliente</a>
        <a href="<?php echo e($returnUrl); ?>" class="btn btn--secondary">Volver a clientes</a>
    </div>
</header>

<div class="card panel-card">
    <?php if ($error): ?>
        <div class="alert alert--error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="admin-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $customerId; ?>">
        <input type="hidden" name="return" value="<?php echo e($returnUrl); ?>">

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
        <button type="submit" class="btn">Guardar cambios</button>
    </form>
</div>

</main>
</div>
</body>
</html>
