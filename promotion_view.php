<?php
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$promotion = $id > 0 ? getPromotionById($conn, $id) : null;

if (!$promotion) {
    header('Location: promociones.php');
    exit;
}

$pageTitle = $promotion['title'];
include 'includes/header.php';
?>

<main>
    <section class="section section--gradient promo-detail-hero">
        <div class="container">
            <a href="promociones.php" class="promo-detail__back">Volver a promociones</a>

            <div class="promo-detail">
                <div class="promo-detail__content">
                    <span class="promo-card__badge">Vigente hasta: <?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?></span>
                    <h1 class="promo-detail__title"><?php echo e($promotion['title']); ?></h1>

                    <?php if ($promotion['short_description'] !== ''): ?>
                        <p class="promo-detail__lead"><?php echo e($promotion['short_description']); ?></p>
                    <?php endif; ?>

                    <div class="promo-detail__body">
                        <p class="promo-detail__body-text"><?php echo nl2br(e($promotion['full_description'])); ?></p>
                    </div>

                    <div class="promo-detail__actions">
                        <a href="contacto.php?promo=<?php echo e($promotion['id']); ?>" class="btn">Quiero esta promoción</a>
                        <a href="promociones.php" class="btn btn--secondary">Seguir explorando</a>
                    </div>
                </div>

                <div class="promo-detail__visual">
                    <img
                        src="<?php echo e($promotion['image_path'] ?: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1350&q=80'); ?>"
                        alt="<?php echo e($promotion['title']); ?>"
                        class="promo-detail__img"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="section section--secondary promo-terms-section">
        <div class="container">
            <article class="card promo-terms">
                <div class="promo-terms__eyebrow">Información importante</div>
                <h2 class="promo-terms__title">Términos y condiciones</h2>
                <div class="promo-terms__content"><?php echo nl2br(e($promotion['terms_conditions'])); ?></div>
            </article>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
