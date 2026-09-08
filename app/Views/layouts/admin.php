<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? 'Panel Administrativo'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <main class="admin-main">
        <?php echo $content; ?>
    </main>
</body>
</html>
