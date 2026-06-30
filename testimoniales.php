<?php 
require_once 'includes/db.php';
$pageTitle = "Testimoniales";

$success = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
    $date = date('Y-m-d');

    if ($name && $message && $rating) {
        $stmt = $conn->prepare("INSERT INTO testimonials (name, message, rating, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $message, $rating, $date);
        if ($stmt->execute()) {
            $success = "¡Gracias! Tu testimonial ha sido recibido.";
        } else {
            $error = "Hubo un error al guardar tu comentario.";
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}

include 'includes/header.php'; 
?>

<main>
    <section class="section section--primary">
        <div class="container">
            <div class="section__title">
                <h1>Lo que dicen nuestros clientes</h1>
                <p class="section__title-text">Las empresas confían en RCN Sistemas para su transformación digital.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid">
                <?php
                $testimonials = getTestimonials($conn);
                if ($testimonials && $testimonials->num_rows > 0):
                    while($row = $testimonials->fetch_assoc()):
                ?>
                    <article class="card testimonial-card">
                        <i class="fas fa-quote-left testimonial-card__quote"></i>
                        <div class="testimonial-card__stars">
                            <?php for($i=0; $i<$row['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-card__text">
                            "<?php echo $row['message']; ?>"
                        </p>
                        <div class="testimonial-card__author">
                            <div class="testimonial-card__avatar">
                                <?php echo substr($row['name'], 0, 1); ?>
                            </div>
                            <div class="testimonial-card__author-info">
                                <h3 class="testimonial-card__author-name"><?php echo $row['name']; ?></h3>
                                <p class="testimonial-card__author-meta"><?php echo date('d M, Y', strtotime($row['date'])); ?></p>
                            </div>
                        </div>
                    </article>
                <?php 
                    endwhile;
                else:
                ?>
                    <p class="section__title-text">Aún no hay testimoniales. ¡Sé el primero en dejar uno!</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Add Testimonial Form -->
    <section class="section section--gray">
        <div class="container">
            <div class="section__title">
                <h2>Déjanos tu opinión</h2>
                <p class="section__title-text">Tu feedback nos ayuda a mejorar cada día.</p>
            </div>

            <div class="card form-card" style="max-width: 600px; margin: 0 auto;">
                <?php if($success): ?>
                    <div class="alert alert--success">
                        <i class="fas fa-check-circle alert__icon"></i>
                        <p><?php echo $success; ?></p>
                    </div>
                <?php else: ?>
                    <?php if($error): ?>
                        <div class="alert alert--error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="testimoniales.php" method="POST" class="form">
                        <div class="form__group">
                            <label for="name" class="form__label">Tu Nombre *</label>
                            <input type="text" id="name" name="name" class="form__input" required>
                        </div>
                        <div class="form__group">
                            <label for="rating" class="form__label">Calificación *</label>
                            <select id="rating" name="rating" class="form__select" required>
                                <option value="5">5 Estrellas - Excelente</option>
                                <option value="4">4 Estrellas - Muy Bueno</option>
                                <option value="3">3 Estrellas - Bueno</option>
                                <option value="2">2 Estrellas - Regular</option>
                                <option value="1">1 Estrella - Pobre</option>
                            </select>
                        </div>
                        <div class="form__group">
                            <label for="message" class="form__label">Tu Comentario *</label>
                            <textarea id="message" name="message" class="form__textarea" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn">Enviar Testimonial</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
