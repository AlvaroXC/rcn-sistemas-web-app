<?php
use RcnSistemas\Core\Security\SessionManager;

$session = new SessionManager();
$session->start();
$baseUrl = rtrim((string) (function_exists('getEnvVar') ? getEnvVar('APP_URL', '') : ''), '/');
if ($baseUrl === '') {
    $baseUrl = rtrim((string) ($_SERVER['RCN_BASE_PATH'] ?? ''), '/');
    if ($baseUrl === '') {
        $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        $baseUrl = $scriptDirectory === '.' || $scriptDirectory === '/' ? '' : rtrim(dirname($scriptDirectory), '/');
    }
}
$assetRoot = dirname(__DIR__, 3);
$assetStamp = max(array_map(static fn (string $path): int => is_file($path) ? (int) filemtime($path) : 0, [$assetRoot . '/css/styles.css', $assetRoot . '/css/admin.css', $assetRoot . '/js/main.js', $assetRoot . '/js/admin.js', $assetRoot . '/js/notifications.js', $assetRoot . '/js/push.js', $assetRoot . '/js/pwa.js']));
$assetVersion = rawurlencode((string) (function_exists('getEnvVar') ? getEnvVar('APP_ASSET_VERSION', '2026.06.27.1') : '2026.06.27.1') . '-' . $assetStamp);
$firebaseWebConfig = [
    'apiKey' => (string) getEnvVar('FIREBASE_API_KEY', ''),
    'authDomain' => (string) getEnvVar('FIREBASE_AUTH_DOMAIN', ''),
    'projectId' => (string) getEnvVar('FIREBASE_PROJECT_ID', ''),
    'messagingSenderId' => (string) getEnvVar('FIREBASE_MESSAGING_SENDER_ID', ''),
    'appId' => (string) getEnvVar('FIREBASE_APP_ID', ''),
];
$firebaseVapidKey = (string) getEnvVar('FIREBASE_VAPID_PUBLIC_KEY', '');
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH) ?: '/admin';
$currentPath = preg_replace('#^' . preg_quote($baseUrl, '#') . '#', '', $currentPath) ?: '/admin';
$adminLabel = $session->userLabel() ?: 'Administrador';
$adminRole = $session->role();
$adminRoleLabel = $adminRole === 'admin' ? 'Administrador' : 'Operador';
$adminInitial = strtoupper(substr($adminLabel, 0, 1));
$notificationContext = $notificationContext ?? ['unread' => 0, 'latestId' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#003b5c">
    <meta name="csrf-token" content="<?php echo htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.webmanifest">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? 'Panel Administrativo'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/styles.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/admin.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-app-base-path="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" data-pwa-version="<?php echo $assetVersion; ?>" data-push-register-url="<?php echo $baseUrl; ?>/admin/notificaciones" data-notification-stream-url="<?php echo $baseUrl; ?>/admin/notificaciones/stream?last_id=<?php echo (int) $notificationContext['latestId']; ?>" data-notification-login-url="<?php echo $baseUrl; ?>/admin/login" data-notification-list-url="<?php echo $baseUrl; ?>/admin/notificaciones" data-notification-unread-count="<?php echo (int) $notificationContext['unread']; ?>" data-notification-sound-url="<?php echo $baseUrl; ?>/assets/audio/notification.mp3" data-firebase-web-config='<?php echo htmlspecialchars(json_encode($firebaseWebConfig ?? [], JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>' data-firebase-vapid-key="<?php echo htmlspecialchars((string) ($firebaseVapidKey ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="admin-layout">
        <script>
            (() => {
                try {
                    if (window.matchMedia('(min-width: 992px)').matches && window.localStorage.getItem('rcn-admin-sidebar-collapsed') === 'true') {
                        document.currentScript.parentElement.classList.add('admin-layout--collapsed');
                    }
                } catch (error) {}
            })();
        </script>
        <header class="mobile-topbar">
            <button type="button" class="mobile-topbar__menu" data-mobile-menu-toggle aria-label="Abrir menú" aria-controls="mobile-admin-sidebar" aria-expanded="false"><i class="fas fa-bars" aria-hidden="true"></i></button>
            <span class="mobile-topbar__logo">Admin<span>RCN</span></span>
            <a href="<?php echo $baseUrl; ?>/admin/notificaciones" class="mobile-topbar__notifications" aria-label="Ver notificaciones" title="Ver notificaciones"><i class="fas fa-bell" aria-hidden="true"></i><span class="sidebar__badge" data-notification-badge <?php echo $notificationContext['unread'] > 0 ? '' : 'hidden'; ?>><?php echo $notificationContext['unread'] > 0 ? (int) $notificationContext['unread'] : ''; ?></span></a>
        </header>
        <aside class="sidebar" id="mobile-admin-sidebar" data-mobile-sidebar>
            <div class="sidebar__header"><h1 class="sidebar__logo"><span class="sidebar__logo-full">Admin<span>RCN</span></span><span class="sidebar__logo-collapsed">RCN</span></h1><button type="button" class="sidebar__toggle" data-sidebar-toggle aria-label="Contraer menú" aria-expanded="true" title="Contraer menú"><i class="fas fa-chevron-left" aria-hidden="true"></i></button></div>
            <button type="button" class="sidebar__close" data-sidebar-close aria-label="Cerrar menú"><i class="fas fa-times" aria-hidden="true"></i><span>Cerrar menú</span></button>
            <section class="sidebar__session" aria-label="Sesión activa"><div class="sidebar__session-avatar" aria-hidden="true"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></div><div class="sidebar__session-body"><p class="sidebar__session-kicker">Sesión activa</p><p class="sidebar__session-user"><?php echo htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8'); ?></p><p class="sidebar__session-role"><?php echo htmlspecialchars($adminRoleLabel, ENT_QUOTES, 'UTF-8'); ?></p></div></section>
            <nav class="sidebar__nav">
                <a href="<?php echo $baseUrl; ?>/admin/" title="Dashboard" class="sidebar__link <?php echo $currentPath === '/admin' ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-chart-line"></i><span class="sidebar__link-label">Dashboard</span></a>
                <a href="<?php echo $baseUrl; ?>/admin/productos" title="Productos" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/productos') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-box"></i><span class="sidebar__link-label">Productos</span></a>
                <a href="<?php echo $baseUrl; ?>/admin/promociones" title="Promociones" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/promociones') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-tag"></i><span class="sidebar__link-label">Promociones</span></a>
                <a href="<?php echo $baseUrl; ?>/admin/tickets" title="Tickets" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/tickets') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-life-ring"></i><span class="sidebar__link-label">Tickets</span></a>
                <a href="<?php echo $baseUrl; ?>/admin/clientes" title="Clientes" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/clientes') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-address-book"></i><span class="sidebar__link-label">Clientes</span></a>
                <a href="<?php echo $baseUrl; ?>/admin/notificaciones" title="Notificaciones" class="sidebar__link sidebar__link--with-badge <?php echo $currentPath === '/admin/notificaciones' ? 'sidebar__link--active' : ''; ?>"><span class="sidebar__link-content"><i class="fas fa-bell"></i><span class="sidebar__link-label">Notificaciones</span></span><span class="sidebar__badge" data-notification-badge <?php echo $notificationContext['unread'] > 0 ? '' : 'hidden'; ?>><?php echo $notificationContext['unread'] > 0 ? (int) $notificationContext['unread'] : ''; ?></span></a>
                <a href="<?php echo $baseUrl; ?>/admin/google-calendar" title="Google Calendar" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/google-calendar') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-calendar-alt"></i><span class="sidebar__link-label">Google Calendar</span></a>
                <?php if ($adminRole === 'admin'): ?><a href="<?php echo $baseUrl; ?>/admin/usuarios" title="Usuarios" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/usuarios') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-users-cog"></i><span class="sidebar__link-label">Usuarios</span></a><?php endif; ?>
                <a href="<?php echo $baseUrl; ?>/admin/logout" title="Cerrar sesión" class="sidebar__link sidebar__link--logout"><i class="fas fa-sign-out-alt"></i><span class="sidebar__link-label">Cerrar sesión</span></a>
            </nav>
        </aside>
        <div class="mobile-sidebar-overlay" data-sidebar-overlay hidden></div>
        <main class="admin-main"><?php echo $content; ?></main>
    </div>
    <script src="<?php echo $baseUrl; ?>/js/main.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
