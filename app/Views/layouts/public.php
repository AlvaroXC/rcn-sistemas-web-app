<?php
$company = require dirname(__DIR__, 3) . '/config/company.php';
$baseUrl = rtrim((string) (function_exists('getEnvVar') ? getEnvVar('APP_URL', '') : ''), '/');
if ($baseUrl === '') {
    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $baseUrl = $scriptDirectory === '.' || $scriptDirectory === '/' ? '' : $scriptDirectory;
}
$assetVersion = rawurlencode((string) (function_exists('getEnvVar') ? getEnvVar('APP_ASSET_VERSION', '2026.06.27.1') : '2026.06.27.1'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RCN Sistemas - Distribuidores de software contable y administrativo CONTPAQi">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? 'RCN Sistemas'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/styles.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/public.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="public-page">
    <header class="header">
        <div class="container header__content">
            <a href="<?php echo $baseUrl; ?>/" class="header__logo"><img src="<?php echo $baseUrl; ?>/assets/images/LOGO RCN Sistemas.jpg" alt="RCN Sistemas" class="header__logo-img"></a>
            <nav class="nav">
                <a href="<?php echo $baseUrl; ?>/" class="nav__link">Inicio</a>
                <a href="<?php echo $baseUrl; ?>/productos" class="nav__link">Productos</a>
                <a href="<?php echo $baseUrl; ?>/promociones" class="nav__link">Promociones</a>
                <a href="<?php echo $baseUrl; ?>/testimoniales" class="nav__link">Testimoniales</a>
                <a href="<?php echo $baseUrl; ?>/contacto" class="nav__link">Contacto</a>
            </nav>
        </div>
    </header>
    <?php echo $content; ?>
    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div class="footer__info"><h3 class="footer__title"><?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?></h3><p class="footer__text">Expertos en consultoría y distribución de software administrativo para empresas que buscan crecer de forma organizada.</p></div>
                <div class="footer__links"><h4 class="footer__title">Enlaces rápidos</h4><ul class="footer__list"><li><a href="<?php echo $baseUrl; ?>/">Inicio</a></li><li><a href="<?php echo $baseUrl; ?>/productos">Catálogo de productos</a></li><li><a href="<?php echo $baseUrl; ?>/promociones">Promociones del mes</a></li><li><a href="<?php echo $baseUrl; ?>/contacto">Contacto</a></li></ul></div>
                <div class="footer__social-wrapper"><h4 class="footer__title">Síguenos</h4><div class="footer__social"><a href="<?php echo htmlspecialchars($company['facebook_url'], ENT_QUOTES, 'UTF-8'); ?>" class="footer__social-link" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a><a href="<?php echo htmlspecialchars($company['whatsapp_url'], ENT_QUOTES, 'UTF-8'); ?>" class="footer__social-link" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a></div></div>
            </div>
            <div class="footer__bottom"><p>&copy; <?php echo date('Y'); ?> RCN Sistemas. Todos los derechos reservados.</p></div>
        </div>
    </footer>
    <div class="floating-btns"><a href="<?php echo htmlspecialchars($company['whatsapp_url'], ENT_QUOTES, 'UTF-8'); ?>" class="float-btn float-btn--whatsapp" target="_blank" rel="noopener" title="Chatea con nosotros"><i class="fab fa-whatsapp"></i></a><button id="btn-top" class="float-btn float-btn--top" type="button" title="Volver arriba"><i class="fas fa-chevron-up"></i></button></div>
    <script src="<?php echo $baseUrl; ?>/js/main.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
