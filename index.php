<?php 
require_once 'includes/db.php';
$pageTitle = "Inicio";
include 'includes/header.php'; 
?>

<main>
    <!-- Hero Section -->
    <section class="hero hero--home">
        <div class="container">
            <h1 class="hero__title">Potencia tu empresa con <br> Software de Clase Mundial</h1>
            <p class="hero__text">Somos expertos en soluciones CONTPAQi para contabilidad, nóminas y administración. Digitaliza tu negocio hoy mismo.</p>
            <div class="hero__actions">
                <a href="productos.php" class="btn">Ver Productos</a>
                <a href="contacto.php" class="btn btn--secondary">Solicitar Demo</a>
            </div>
        </div>
    </section>

    <section class="trust-strip">
        <div class="container">
            <div class="trust-strip__card">
                <div class="trust-strip__content">
                    <p class="trust-strip__eyebrow">Respaldo comercial</p>
                    <h2 class="trust-strip__title">Distribuidor Asociado Plata CONTPAQi</h2>
                    <p class="trust-strip__text">En RCN Sistemas te atendemos con respaldo oficial para licenciamiento, renovación e implementación de soluciones CONTPAQi, para que tomes decisiones con mayor confianza desde el primer contacto.</p>
                </div>
                <div class="trust-strip__visual">
                    <img src="src/img/PLATARCN.jpg" alt="Distintivo Distribuidor Asociado Plata CONTPAQi de RCN Sistemas" class="trust-strip__img">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="section">
        <div class="container">
            <div class="section__title">
                <h2>Soluciones Destacadas</h2>
                <p class="section__title-text">Descubre las herramientas que están transformando la administración en México.</p>
            </div>
            
            <div class="grid">
                <?php
                $featured = getProducts($conn, 3);
                if ($featured && $featured->num_rows > 0):
                    while($row = $featured->fetch_assoc()):
                ?>
                    <article class="card product-card">
                        <div class="product-card__visual">
                            <img src="<?php echo $row['image_path'] ?: 'https://via.placeholder.com/150'; ?>" alt="<?php echo $row['name']; ?>">
                        </div>
                        <div class="product-card__info">
                            <h3 class="product-card__title"><?php echo $row['name']; ?></h3>
                            <p class="product-card__text"><?php echo $row['short_description']; ?></p>
                            <a href="product_view.php?id=<?php echo $row['id']; ?>" class="btn btn--secondary btn--full">Más información</a>
                        </div>
                    </article>
                <?php 
                    endwhile;
                else:
                ?>
                    <p class="section__title-text">Próximamente más productos.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section section--gray">
        <div class="container">
            <div class="section__title">
                <h2>¿Por qué RCN Sistemas?</h2>
                <p class="section__title-text">Nuestra experiencia nos permite ofrecerte el mejor acompañamiento.</p>
            </div>
            
            <div class="grid">
                <div class="card" style="padding: 3rem; text-align: center; border: none; background: transparent; box-shadow: none;">
                    <i class="fas fa-certificate" style="font-size: 4rem; color: var(--accent); margin-bottom: 2rem;"></i>
                    <h3>Certificados</h3>
                    <p>Contamos con las certificaciones oficiales de CONTPAQi para tu total seguridad.</p>
                </div>
                <div class="card" style="padding: 3rem; text-align: center; border: none; background: transparent; box-shadow: none;">
                    <i class="fas fa-headset" style="font-size: 4rem; color: var(--accent); margin-bottom: 2rem;"></i>
                    <h3>Soporte Experto</h3>
                    <p>Atención personalizada y resolución de dudas técnicas en tiempo récord.</p>
                </div>
                <div class="card" style="padding: 3rem; text-align: center; border: none; background: transparent; box-shadow: none;">
                    <i class="fas fa-rocket" style="font-size: 4rem; color: var(--accent); margin-bottom: 2rem;"></i>
                    <h3>Implementación Ágil</h3>
                    <p>Configuramos tus sistemas para que comiences a trabajar de inmediato.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
