<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? 'RCN Sistemas'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container header__content">
            <a href="/index.php" class="header__logo">RCN Sistemas</a>
            <nav class="nav">
                <a href="/index.php" class="nav__link">Inicio</a>
                <a href="/productos.php" class="nav__link">Productos</a>
                <a href="/promociones.php" class="nav__link">Promociones</a>
                <a href="/contacto.php" class="nav__link">Contacto</a>
            </nav>
        </div>
    </header>
    <?php echo $content; ?>
</body>
</html>
