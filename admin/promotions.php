<?php 
include 'includes/admin_header.php'; 
require_once '../includes/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = '';
$promotionFlash = get_flash_message('promotion_notice');
$promotionNotice = trim((string) ($promotionFlash['message'] ?? ''));
$promotionNoticeType = trim((string) ($promotionFlash['type'] ?? 'success'));
if (!in_array($action, ['list', 'new', 'edit'], true)) {
    $action = 'list';
}
if (!in_array($promotionNoticeType, ['success', 'error'], true)) {
    $promotionNoticeType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $formAction = $_POST['form_action'] ?? 'save';
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : $id;

    if ($formAction === 'delete' && $postId > 0) {
        $stmt = $conn->prepare('DELETE FROM promotions WHERE id = ?');
        $stmt->bind_param('i', $postId);
        if ($stmt->execute()) {
            set_flash_message('promotion_notice', 'La promoción se eliminó correctamente.', 'success');
            redirect_to('promotions.php');
        }
        $error = 'No se pudo borrar la promoción.';
    }

    if ($formAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $fullDescription = trim($_POST['full_description'] ?? '');
        $termsConditions = trim($_POST['terms_conditions'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if (
            $title === ''
            || $shortDescription === ''
            || $fullDescription === ''
            || $termsConditions === ''
            || !validate_date_value($start)
            || !validate_date_value($end)
        ) {
            $error = 'Completa todos los campos obligatorios con fechas válidas.';
        } elseif ($start > $end) {
            $error = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
        } else {
            $img_path = '';
            if ($postId > 0) {
                $currentStmt = $conn->prepare('SELECT image_path FROM promotions WHERE id = ?');
                $currentStmt->bind_param('i', $postId);
                $currentStmt->execute();
                $currentPromotion = $currentStmt->get_result()->fetch_assoc();
                $img_path = $currentPromotion['image_path'] ?? '';
            }

            $imageUpload = save_uploaded_file('image', __DIR__ . '/../uploads/images', 'uploads/images', image_upload_rules());
            if (!$imageUpload['ok']) {
                $error = $imageUpload['error'];
            } else {
                if ($imageUpload['path']) {
                    $img_path = $imageUpload['path'];
                }

                if ($postId > 0) {
                    $stmt = $conn->prepare('UPDATE promotions SET title=?, short_description=?, full_description=?, terms_conditions=?, image_path=?, start_date=?, end_date=?, active=? WHERE id=?');
                    $stmt->bind_param('sssssssii', $title, $shortDescription, $fullDescription, $termsConditions, $img_path, $start, $end, $active, $postId);
                } else {
                    $stmt = $conn->prepare('INSERT INTO promotions (title, short_description, full_description, terms_conditions, image_path, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssssssi', $title, $shortDescription, $fullDescription, $termsConditions, $img_path, $start, $end, $active);
                }

                if ($stmt->execute()) {
                    set_flash_message('promotion_notice', 'La promoción se guardó correctamente.', 'success');
                    redirect_to('promotions.php');
                }
                $error = 'No se pudo guardar la promoción.';
            }
        }
    }
}

$current = [
    'title' => '',
    'short_description' => '',
    'full_description' => '',
    'terms_conditions' => '',
    'image_path' => '',
    'start_date' => '',
    'end_date' => '',
    'active' => 1,
];
if ($id > 0) {
    $promoStmt = $conn->prepare('SELECT * FROM promotions WHERE id = ?');
    $promoStmt->bind_param('i', $id);
    $promoStmt->execute();
    $currentPromotion = $promoStmt->get_result()->fetch_assoc();
    if ($currentPromotion) {
        $current = array_merge($current, normalizePromotionRow($currentPromotion));
    }
}
?>

<header class="admin-header">
    <h2>Gestionar Promociones</h2>
    <?php if ($action == 'list'): ?>
        <a href="promotions.php?action=new" class="btn">Nueva Promoción</a>
    <?php else: ?>
        <a href="promotions.php" class="btn btn--secondary">Volver al Listado</a>
    <?php endif; ?>
</header>

<?php if ($promotionNotice !== ''): ?>
    <div class="alert alert--<?php echo e($promotionNoticeType); ?>" data-auto-dismiss="5000"><?php echo e($promotionNotice); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Vigencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $res = getPromotions($conn, false);
                while ($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo e($row['id']); ?></td>
                    <td><strong><?php echo e($row['title']); ?></strong></td>
                    <td>
                        <?php if ($row['active']): ?>
                            <span class="status status--active"><i class="fas fa-check-circle" aria-hidden="true"></i> Activa</span>
                        <?php else: ?>
                            <span class="status status--inactive"><i class="fas fa-times-circle" aria-hidden="true"></i> Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['start_date'])) . ' - ' . date('d/m/Y', strtotime($row['end_date'])); ?></td>
                    <td>
                        <div class="table__actions">
                            <a href="promotions.php?action=edit&id=<?php echo e($row['id']); ?>" class="table__action"><i class="fas fa-edit" aria-hidden="true"></i> Editar</a>
                            <form action="promotions.php" method="POST" class="inline-form" onsubmit="return confirm('¿Seguro?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                <button type="submit" class="table__action table__action--danger table__action-button"><i class="fas fa-trash" aria-hidden="true"></i> Borrar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="card panel-card">
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="id" value="<?php echo e($id); ?>">
            <div class="admin-form__group">
                <label class="admin-form__label">Título de la Promoción</label>
                <input type="text" name="title" class="admin-form__input" required value="<?php echo e($current['title']); ?>">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Descripción Corta</label>
                <textarea name="short_description" class="admin-form__textarea" rows="3" required><?php echo e($current['short_description']); ?></textarea>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Descripción Completa</label>
                <textarea name="full_description" class="admin-form__textarea" rows="5" required><?php echo e($current['full_description']); ?></textarea>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Términos y Condiciones</label>
                <textarea name="terms_conditions" class="admin-form__textarea" rows="5" required><?php echo e($current['terms_conditions']); ?></textarea>
            </div>

            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label">Fecha de Inicio</label>
                    <input type="date" name="start_date" class="admin-form__input" required value="<?php echo e($current['start_date']); ?>">
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label">Fecha de Fin</label>
                    <input type="date" name="end_date" class="admin-form__input" required value="<?php echo e($current['end_date']); ?>">
                </div>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Imagen Promocional</label>
                <input type="file" name="image" class="admin-form__input" accept="image/*">
                <input type="hidden" name="current_img" value="<?php echo e($current['image_path']); ?>">
                <?php if ($current['image_path']): ?><small class="admin-current-media">Actual: <img src="../<?php echo e($current['image_path']); ?>" alt="<?php echo e($current['title']); ?>"></small><?php endif; ?>
            </div>

            <div class="admin-form__group admin-form__check">
                <input type="checkbox" name="active" id="active" <?php echo $current['active'] ? 'checked' : ''; ?>>
                <label for="active" class="admin-form__label">Promoción Activa</label>
            </div>

            <button type="submit" class="btn">Guardar Promoción</button>
        </form>
    </div>
<?php endif; ?>

</main>
</div>
</body>
</html>
