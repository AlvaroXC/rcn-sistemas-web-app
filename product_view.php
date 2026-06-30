<?php 
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = $conn->prepare("SELECT * FROM products WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: productos.php");
    exit;
}

$pageTitle = $product['name'];
include 'includes/header.php'; 

// Fetch benefits
$benefitsQuery = $conn->prepare("SELECT * FROM benefits WHERE product_id = ?");
$benefitsQuery->bind_param("i", $id);
$benefitsQuery->execute();
$benefits = $benefitsQuery->get_result();
?>

<main>
    <!-- Product Hero -->
    <section class="section">
        <div class="container">
            <div class="product-detail">
                <div class="product-detail__visual">
                    <img src="<?php echo $product['image_path'] ?: 'https://via.placeholder.com/400'; ?>" alt="<?php echo $product['name']; ?>" class="product-detail__img">
                </div>
                
                <div class="product-detail__info">
                    <h1 class="product-detail__title"><?php echo $product['name']; ?></h1>
                    <p class="product-detail__info-text">
                        <?php echo $product['long_description']; ?>
                    </p>
                    <div class="product-detail__actions">
                        <a href="contacto.php?product=<?php echo $id; ?>" class="btn">Cotizar ahora</a>
                        <?php if($product['pdf_path']): ?>
                            <a href="<?php echo $product['pdf_path']; ?>" target="_blank" class="btn btn--secondary">
                                <i class="fas fa-file-pdf" style="margin-right: 1rem;"></i> Descargar Ficha
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <?php if ($benefits && $benefits->num_rows > 0): ?>
    <section class="section section--secondary">
        <div class="container">
            <div class="section__title">
                <h2 style="color: white;">Beneficios Clave</h2>
                <p class="section__title-text">Por qué este sistema es la mejor inversión para tu negocio.</p>
            </div>
            
            <div class="grid">
                <?php while($benefit = $benefits->fetch_assoc()): ?>
                <div class="benefit">
                    <i class="fas fa-check-circle benefit__icon"></i>
                    <h3 class="benefit__title"><?php echo $benefit['title']; ?></h3>
                    <p class="benefit__text"><?php echo $benefit['description']; ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Video Section -->
    <?php if ($product['video_link']): ?>
    <section class="section">
        <div class="container">
            <div class="section__title">
                <h2>Mira cómo funciona</h2>
                <p class="section__title-text">Una breve demostración de las capacidades del sistema.</p>
            </div>
            
            <div class="video-wrapper">
                <div class="video-container">
                    <iframe 
                        src="<?php echo str_replace('watch?v=', 'embed/', $product['video_link']); ?>" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
