<section class="section">
    <h1><?php echo htmlspecialchars((string) ($pageTitle ?? 'Producto'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if (!empty($error)): ?>
        <p class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="name">Nombre</label>
        <input id="name" name="name" required>
        <button type="submit">Guardar</button>
    </form>
</section>
