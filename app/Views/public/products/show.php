<section class="section">
    <h1><?php echo htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($product->longDescription, ENT_QUOTES, 'UTF-8')); ?></p>

    <?php if ($product->benefits): ?>
        <h2>Beneficios</h2>
        <ul>
            <?php foreach ($product->benefits as $benefit): ?>
                <li>
                    <strong><?php echo htmlspecialchars($benefit->title, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php echo htmlspecialchars($benefit->description, ENT_QUOTES, 'UTF-8'); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
