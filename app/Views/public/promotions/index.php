<section class="section"><h1>Promociones</h1><div class="grid">
<?php foreach ($promotions as $promotion): ?><article class="card"><h2><?php echo htmlspecialchars($promotion->title, ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars($promotion->shortDescription, ENT_QUOTES, 'UTF-8'); ?></p><a href="/promotion_view.php?id=<?php echo $promotion->id; ?>">Ver detalle</a></article><?php endforeach; ?>
</div></section>
