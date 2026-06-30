<?php 
require_once 'includes/db.php';
$pageTitle = "Productos";
include 'includes/header.php'; 
?>

<main>
    <section class="section section--gray">
        <div class="container">
            <div class="section__title">
                <h1>Nuestros Productos</h1>
                <p class="section__title-text">La gama más completa de software empresarial en México.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid">
                <?php
                $products = getProducts($conn);
                if ($products && $products->num_rows > 0):
                    while($row = $products->fetch_assoc()):
                ?>
                    <article class="card product-card">
                        <div class="product-card__visual">
                            <img src="<?php echo $row['image_path'] ?: 'https://via.placeholder.com/150'; ?>" alt="<?php echo $row['name']; ?>">
                        </div>
                        <div class="product-card__info">
                            <h3 class="product-card__title"><?php echo $row['name']; ?></h3>
                            <p class="product-card__text"><?php echo $row['short_description']; ?></p>
                            <div class="product-card__actions">
                                <a href="product_view.php?id=<?php echo $row['id']; ?>" class="btn btn--secondary btn--full">Detalles</a>
                                <?php if($row['pdf_path']): ?>
                                    <a href="<?php echo $row['pdf_path']; ?>" target="_blank" class="btn" title="Descargar Ficha"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="section__title" style="grid-column: 1/-1; padding: 10rem 0;">
                        <i class="fas fa-box-open" style="font-size: 6rem; color: var(--border); margin-bottom: 2rem;"></i>
                        <p class="section__title-text">Aún no hay productos registrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
