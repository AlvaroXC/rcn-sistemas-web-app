<?php 
require_once 'includes/db.php';
$pageTitle = 'Promociones del Mes';
include 'includes/header.php'; 
?>

<main>
    <section class="section section--gradient">
        <div class="container">
            <div class="section__title">
                <h1>Promociones Exclusivas</h1>
                <p class="section__title-text">Aprovecha nuestros descuentos y paquetes especiales del mes para renovar tu empresa.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid promo-grid">
                <?php
                $promos = getPromotions($conn);
                if ($promos && $promos->num_rows > 0):
                    while ($row = $promos->fetch_assoc()):
                        $promotion = normalizePromotionRow($row);
                ?>
                    <a href="promotion_view.php?id=<?php echo e($promotion['id']); ?>" class="card promo-card promo-card--link" aria-label="Ver detalle de la promoción <?php echo e($promotion['title']); ?>">
                        <div class="promo-card__visual" style="background-image: url('<?php echo e($promotion['image_path'] ?: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1350&q=80'); ?>');">
                        </div>
                        <div class="promo-card__info">
                            <span class="promo-card__badge">Vigente hasta: <?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?></span>
                            <h2 class="promo-card__title"><?php echo e($promotion['title']); ?></h2>
                            <p class="promo-card__text"><?php echo e($promotion['short_description']); ?></p>
                            <span class="promo-card__cta">Explorar detalle</span>
                        </div>
                    </a>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="empty-state">
                        <i class="fas fa-tag empty-state__icon" aria-hidden="true"></i>
                        <p class="section__title-text">Por el momento no hay promociones activas. ¡Vuelve pronto!</p>
                        <a href="productos.php" class="btn btn--secondary">Ver catálogo regular</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
