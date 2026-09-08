<section class="section">
    <h1>Productos</h1>
    <?php if (!$products): ?>
        <p>No hay productos disponibles.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $product): ?>
                <article class="card">
                    <h2><?php echo htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($product->shortDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                    <a href="/product_view.php?id=<?php echo $product->id; ?>">Ver detalles</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
