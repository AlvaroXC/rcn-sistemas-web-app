<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RCN Sistemas - Distribuidores de software contable y administrativo CONTPAQi">
    <title><?php echo isset($pageTitle) ? $pageTitle . " | RCN Sistemas" : "RCN Sistemas - Software Empresarial"; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    
    <!-- Icons (Font Awesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__content">
            <a href="index.php" class="header__logo">
                <img src="src/img/LOGO RCN Sistemas.jpg" alt="RCN Sistemas" class="header__logo-img">
            </a>
            
            <nav class="nav">
                <a href="index.php" class="nav__link">Inicio</a>
                <a href="productos.php" class="nav__link">Productos</a>
                <a href="promociones.php" class="nav__link">Promociones</a>
                <a href="testimoniales.php" class="nav__link">Testimoniales</a>
                <a href="contacto.php" class="nav__link">Contacto</a>
            </nav>
        </div>
    </header>
