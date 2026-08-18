<?php 
include 'includes/admin_header.php'; 
require_once '../includes/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$productFlash = get_flash_message('product_notice');
$productNotice = trim((string) ($productFlash['message'] ?? ''));
$productNoticeType = trim((string) ($productFlash['type'] ?? 'success'));

if (!in_array($action, ['list', 'new', 'edit'], true)) {
    $action = 'list';
}

if (!in_array($productNoticeType, ['success', 'error'], true)) {
    $productNoticeType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $formAction = trim((string) ($_POST['form_action'] ?? 'save'));

    if ($formAction === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $deleteId);

            if ($stmt->execute()) {
                set_flash_message('product_notice', 'El producto se eliminó correctamente.', 'success');
                redirect_to('products.php');
            }
        }

        $error = 'No se pudo borrar el producto.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $short_desc = trim((string) ($_POST['short_description'] ?? ''));
        $long_desc = trim((string) ($_POST['long_description'] ?? ''));
        $video = trim((string) ($_POST['video_link'] ?? ''));
    
        $pdf_path = $_POST['current_pdf'] ?? "";
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
            $pdf_name = time() . "_" . $_FILES['pdf']['name'];
            $target = "../uploads/pdfs/" . $pdf_name;
            if (move_uploaded_file($_FILES['pdf']['tmp_name'], $target)) {
                $pdf_path = "uploads/pdfs/" . $pdf_name;
            }
        }

        $img_path = $_POST['current_img'] ?? "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $img_name = time() . "_" . $_FILES['image']['name'];
            $target = "../uploads/images/" . $img_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $img_path = "uploads/images/" . $img_name;
            }
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, short_description=?, long_description=?, video_link=?, pdf_path=?, image_path=? WHERE id=?");
            $stmt->bind_param("ssssssi", $name, $short_desc, $long_desc, $video, $pdf_path, $img_path, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, short_description, long_description, video_link, pdf_path, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $short_desc, $long_desc, $video, $pdf_path, $img_path);
        }

        if ($stmt->execute()) {
            $product_id = $id > 0 ? $id : $conn->insert_id;
            if ($id > 0) {
                $deleteBenefitsStmt = $conn->prepare("DELETE FROM benefits WHERE product_id = ?");
                $deleteBenefitsStmt->bind_param("i", $id);
                $deleteBenefitsStmt->execute();
            }
            
            if (isset($_POST['benefit_titles'])) {
                foreach ($_POST['benefit_titles'] as $key => $title) {
                    if (!empty($title)) {
                        $desc = $_POST['benefit_descs'][$key];
                        $b_stmt = $conn->prepare("INSERT INTO benefits (product_id, title, description) VALUES (?, ?, ?)");
                        $b_stmt->bind_param("iss", $product_id, $title, $desc);
                        $b_stmt->execute();
                    }
                }
            }
            set_flash_message('product_notice', 'El producto se guardó correctamente.', 'success');
            redirect_to('products.php');
        }

        $error = 'No se pudo guardar el producto.';
    }
}

$current = ['name'=>'', 'short_description'=>'', 'long_description'=>'', 'video_link'=>'', 'pdf_path'=>'', 'image_path'=>''];
$benefits = [];
if ($id > 0) {
    $res = $conn->query("SELECT * FROM products WHERE id = $id");
    $current = $res->fetch_assoc();
    $b_res = $conn->query("SELECT * FROM benefits WHERE product_id = $id");
    while($b = $b_res->fetch_assoc()) $benefits[] = $b;
}
?>

<header class="admin-header">
    <h2>Gestionar Productos</h2>
    <?php if($action == 'list'): ?>
        <a href="products.php?action=new" class="btn">Nuevo Producto</a>
    <?php else: ?>
        <a href="products.php" class="btn btn--secondary">Volver al Listado</a>
    <?php endif; ?>
</header>

<?php if($productNotice !== ''): ?>
    <div class="alert alert--<?php echo e($productNoticeType); ?>" data-auto-dismiss="5000"><?php echo e($productNotice); ?></div>
<?php endif; ?>

<?php if($error !== ''): ?>
    <div class="alert alert--error"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if($action == 'list'): ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $res = getProducts($conn);
                while($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><img src="../<?php echo $row['image_path'] ?: 'https://via.placeholder.com/50'; ?>" width="50" style="border-radius: 4px;"></td>
                    <td><strong><?php echo $row['name']; ?></strong></td>
                    <td>
                        <a href="products.php?action=edit&id=<?php echo $row['id']; ?>" style="color: var(--secondary); margin-right: 1.5rem;"><i class="fas fa-edit"></i> Editar</a>
                        <form action="products.php" method="POST" class="inline-form" onsubmit="return confirm('¿Seguro?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button type="submit" class="table__action table__action--danger table__action-button"><i class="fas fa-trash"></i> Borrar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="card" style="padding: 3rem;">
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="form_action" value="save">
            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label">Nombre del Producto</label>
                    <input type="text" name="name" class="admin-form__input" required value="<?php echo $current['name']; ?>">
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label">Enlace de Video (YouTube)</label>
                    <input type="text" name="video_link" class="admin-form__input" value="<?php echo $current['video_link']; ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Descripción Corta</label>
                <input type="text" name="short_description" class="admin-form__input" required value="<?php echo $current['short_description']; ?>">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Descripción Larga</label>
                <textarea name="long_description" class="admin-form__textarea" rows="5" required><?php echo $current['long_description']; ?></textarea>
            </div>

            <div class="admin-form__row">
                <div class="admin-form__group">
                    <label class="admin-form__label">Ficha Técnica (PDF)</label>
                    <input type="file" name="pdf" class="admin-form__input" accept=".pdf">
                    <input type="hidden" name="current_pdf" value="<?php echo $current['pdf_path']; ?>">
                    <?php if($current['pdf_path']): ?><small>Actual: <?php echo basename($current['pdf_path']); ?></small><?php endif; ?>
                </div>
                <div class="admin-form__group">
                    <label class="admin-form__label">Imagen del Producto</label>
                    <input type="file" name="image" class="admin-form__input" accept="image/*">
                    <input type="hidden" name="current_img" value="<?php echo $current['image_path']; ?>">
                    <?php if($current['image_path']): ?><small>Actual: <img src="../<?php echo $current['image_path']; ?>" height="30"></small><?php endif; ?>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px;">
                <h3 style="margin-bottom: 2rem;">Beneficios del Producto</h3>
                <div id="benefits-container">
                    <?php if(empty($benefits)): ?>
                        <div class="benefit-item">
                            <input type="text" name="benefit_titles[]" class="admin-form__input" placeholder="Título">
                            <input type="text" name="benefit_descs[]" class="admin-form__input" placeholder="Descripción">
                        </div>
                    <?php else: ?>
                        <?php foreach($benefits as $b): ?>
                        <div class="benefit-item">
                            <input type="text" name="benefit_titles[]" class="admin-form__input" value="<?php echo $b['title']; ?>">
                            <input type="text" name="benefit_descs[]" class="admin-form__input" value="<?php echo $b['description']; ?>">
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addBenefit()" class="benefit-btn-add">+ Añadir otro beneficio</button>
            </div>

            <button type="submit" class="btn">Guardar Producto</button>
        </form>
    </div>

    <script>
    function addBenefit() {
        const container = document.getElementById('benefits-container');
        const div = document.createElement('div');
        div.className = 'benefit-item';
        div.innerHTML = `
            <input type="text" name="benefit_titles[]" class="admin-form__input" placeholder="Título">
            <input type="text" name="benefit_descs[]" class="admin-form__input" placeholder="Descripción">
        `;
        container.appendChild(div);
    }
    </script>
<?php endif; ?>

</main>
</div>
</body>
</html>
