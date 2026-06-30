<?php 
require_once 'includes/db.php';
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

start_secure_session(false);

$pageTitle = 'Contacto';
$messageSent = false;
$error = false;
$contextLabel = '';

$productContextId = isset($_GET['product']) ? (int) $_GET['product'] : (int) ($_POST['product_id'] ?? 0);
$promoContextId = isset($_GET['promo']) ? (int) $_GET['promo'] : (int) ($_POST['promo_id'] ?? 0);

if ($productContextId > 0) {
    $contextStmt = $conn->prepare('SELECT name FROM products WHERE id = ?');
    $contextStmt->bind_param('i', $productContextId);
    $contextStmt->execute();
    $contextResult = $contextStmt->get_result();
    if ($contextProduct = $contextResult->fetch_assoc()) {
        $contextLabel = 'Cotización para producto: ' . $contextProduct['name'];
    }
} elseif ($promoContextId > 0) {
    $contextStmt = $conn->prepare('SELECT title FROM promotions WHERE id = ?');
    $contextStmt->bind_param('i', $promoContextId);
    $contextStmt->execute();
    $contextResult = $contextStmt->get_result();
    if ($contextPromo = $contextResult->fetch_assoc()) {
        $contextLabel = 'Interés en promoción: ' . $contextPromo['title'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $nombre   = isset($_POST['nombre']) ? htmlspecialchars(trim($_POST['nombre'])) : null;
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $telefono = isset($_POST['telefono']) ? htmlspecialchars(trim($_POST['telefono'])) : null;
    $opcion   = isset($_POST['opcion']) ? htmlspecialchars(trim($_POST['opcion'])) : null;
    $mensaje  = isset($_POST['mensaje']) ? htmlspecialchars(trim($_POST['mensaje'])) : null;
    $allowedOptions = ['Compra', 'Soporte', 'Informacion'];
    $imageValidation = validate_uploaded_file('imagen', image_upload_rules());
    
    if (!$imageValidation['ok']) {
        $error = $imageValidation['error'];
    } elseif ($nombre && $email && $opcion && is_allowed_choice($opcion, $allowedOptions) && $mensaje) {
        $phpmailer = new PHPMailer();
        $phpmailer->isSMTP();
        
        $phpmailer->Host = $_ENV['SMTP_HOST'] ?? 'localhost'; 
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $_ENV['SMTP_PORT'] ?? 587;
        $phpmailer->Username = $_ENV['SMTP_USER'] ?? '';
        $phpmailer->Password = $_ENV['SMTP_PASS'] ?? '';
        $phpmailer->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';

        $phpmailer->addAddress('contacto@rcnsistemas.com');
        $phpmailer->setFrom('contacto@rcnsistemas.com', 'Web Contact');

        if ($imageValidation['file']) {
            $phpmailer->addAttachment($imageValidation['file']['tmp_name'], $imageValidation['file']['name']);
        }

        $phpmailer->Subject = 'Nuevo mensaje de contacto - Web RCN';
        $phpmailer->isHTML(true);
        $phpmailer->CharSet = 'UTF-8';

        $contenido = '<html>';
        $contenido.= '<h2>Nuevo mensaje de contacto</h2>';
        $contenido.= '<p><strong>Nombre:</strong> '. $nombre .'</p>';
        $contenido.= '<p><strong>Email:</strong> '. $email .'</p>';
        $contenido.= '<p><strong>Teléfono:</strong> '. $telefono .'</p>';
        $contenido.= '<p><strong>Opción:</strong> '. $opcion .'</p>';
        if ($contextLabel) {
            $contenido.= '<p><strong>Contexto:</strong> '. htmlspecialchars($contextLabel, ENT_QUOTES, 'UTF-8') .'</p>';
        }
        $contenido.= '<p><strong>Mensaje:</strong><br>'. nl2br($mensaje) .'</p>';
        $contenido.= '</html>';

        $phpmailer->Body = $contenido;
        
        if ($phpmailer->send()) {
            $messageSent = true;
        } else {
            error_log('Contact mail error: ' . $phpmailer->ErrorInfo);
            $error = 'No se pudo enviar el mensaje en este momento. Inténtalo más tarde.';
        }
    } else {
        if (!$email && isset($_POST['email'])) {
            $error = 'El formato del correo electrónico no es válido.';
        } elseif ($opcion && !is_allowed_choice($opcion, $allowedOptions)) {
            $error = 'Selecciona un tipo de consulta válido.';
        } else {
            $error = 'Por favor, completa todos los campos obligatorios.';
        }
    }
}

include 'includes/header.php'; 
?>

<main>
    <section class="section section--gray">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <h1 class="contact-info__title">Contáctanos</h1>
                    <p class="contact-info__text">Estamos listos para ayudarte a elegir la mejor solución para tu empresa. Déjanos tus datos y un consultor te contactará en breve.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-item__icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-item__content">
                                <h3 class="contact-item__title">Teléfono</h3>
                                <p class="contact-item__text">+52 (999) 9942427</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-item__icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-item__content">
                                <h3 class="contact-item__title">Correo</h3>
                                <p class="contact-item__text">contacto@rcnsistemas.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-item__icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-item__content">
                                <h3 class="contact-item__title">Ubicación</h3>
                                <p class="contact-item__text">Mérida, Yucatán, México</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <div class="card form-card">
                        <?php if ($messageSent): ?>
                            <div class="alert alert--success">
                                <i class="fas fa-check-circle alert__icon" aria-hidden="true"></i>
                                <h2>¡Mensaje Enviado!</h2>
                                <p>Gracias por contactarnos. Un asesor se comunicará contigo muy pronto.</p>
                                <a href="index.php" class="btn alert__action">Volver al Inicio</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert--error">
                                    <?php echo e($error); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($contextLabel): ?>
                                <div class="alert alert--info">
                                    <strong>Consulta relacionada:</strong> <?php echo e($contextLabel); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="contacto.php" method="POST" enctype="multipart/form-data" class="form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo e($productContextId); ?>">
                                <input type="hidden" name="promo_id" value="<?php echo e($promoContextId); ?>">
                                <div class="form__group">
                                    <label for="nombre" class="form__label">Nombre Completo *</label>
                                    <input type="text" id="nombre" name="nombre" class="form__input" required value="<?php echo e($_POST['nombre'] ?? ''); ?>">
                                </div>
                                <div class="form__row">
                                    <div class="form__group">
                                        <label for="email" class="form__label">Correo Electrónico *</label>
                                        <input type="email" id="email" name="email" class="form__input" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                                    </div>
                                    <div class="form__group">
                                        <label for="telefono" class="form__label">Teléfono</label>
                                        <input type="tel" id="telefono" name="telefono" class="form__input" value="<?php echo e($_POST['telefono'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form__group">
                                    <label for="opcion" class="form__label">Tipo de Consulta *</label>
                                    <?php $selectedOption = $_POST['opcion'] ?? ($contextLabel ? 'Compra' : ''); ?>
                                    <select id="opcion" name="opcion" class="form__select" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="Compra" <?php echo $selectedOption === 'Compra' ? 'selected' : ''; ?>>Compra</option>
                                        <option value="Soporte" <?php echo $selectedOption === 'Soporte' ? 'selected' : ''; ?>>Soporte Técnico</option>
                                        <option value="Informacion" <?php echo $selectedOption === 'Informacion' ? 'selected' : ''; ?>>Información General</option>
                                    </select>
                                </div>
                                <div class="form__group">
                                    <label for="mensaje" class="form__label">Mensaje *</label>
                                    <textarea id="mensaje" name="mensaje" class="form__textarea" rows="5" required><?php echo e($_POST['mensaje'] ?? ''); ?></textarea>
                                </div>
                                <div class="form__group">
                                    <label for="imagen" class="form__label">Adjuntar Imagen (opcional)</label>
                                    <input type="file" id="imagen" name="imagen" class="form__input" accept="image/*">
                                </div>
                                <button type="submit" class="btn">Enviar Mensaje</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
