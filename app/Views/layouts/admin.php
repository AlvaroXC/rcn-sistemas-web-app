<?php
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliNotificationRepository;

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
$assetVersion = rawurlencode((string) (function_exists('getEnvVar') ? getEnvVar('APP_ASSET_VERSION', '2026.06.27.1') : '2026.06.27.1') . '-' . (filemtime(dirname(__DIR__, 3) . '/css/admin.css') ?: 0));
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
$notificationContext = ['unread' => 0, 'latestId' => 0];
if ($session->isAuthenticated()) {
    $layoutApplication = require dirname(__DIR__, 3) . '/app/Bootstrap/app.php';
    $notificationRepository = new MysqliNotificationRepository($layoutApplication['database']->get());
    $notificationContext = ['unread' => $notificationRepository->unreadCount($session->userId()), 'latestId' => $notificationRepository->latestId($session->userId())];
}
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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/styles.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/admin.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-app-base-path="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" data-push-register-url="<?php echo $baseUrl; ?>/admin/notificaciones" data-notification-stream-url="<?php echo $baseUrl; ?>/admin/notificaciones/stream?last_id=<?php echo (int) $notificationContext['latestId']; ?>" data-notification-login-url="<?php echo $baseUrl; ?>/admin/login" data-notification-list-url="<?php echo $baseUrl; ?>/admin/notificaciones" data-notification-unread-count="<?php echo (int) $notificationContext['unread']; ?>" data-notification-sound-url="<?php echo $baseUrl; ?>/assets/audio/notification.mp3" data-firebase-web-config='<?php echo htmlspecialchars(json_encode($firebaseWebConfig ?? [], JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>' data-firebase-vapid-key="<?php echo htmlspecialchars((string) ($firebaseVapidKey ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="admin-layout">
        <aside class="sidebar">
            <h1 class="sidebar__logo">Admin<span>RCN</span></h1>
            <section class="sidebar__session" aria-label="Sesión activa"><div class="sidebar__session-avatar" aria-hidden="true"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></div><div class="sidebar__session-body"><p class="sidebar__session-kicker">Sesión activa</p><p class="sidebar__session-user"><?php echo htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8'); ?></p><p class="sidebar__session-role"><?php echo htmlspecialchars($adminRoleLabel, ENT_QUOTES, 'UTF-8'); ?></p></div></section>
            <nav class="sidebar__nav">
                <a href="<?php echo $baseUrl; ?>/admin/" class="sidebar__link <?php echo $currentPath === '/admin' ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="<?php echo $baseUrl; ?>/admin/productos" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/productos') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-box"></i> Productos</a>
                <a href="<?php echo $baseUrl; ?>/admin/promociones" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/promociones') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-tag"></i> Promociones</a>
                <a href="<?php echo $baseUrl; ?>/admin/tickets" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/tickets') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-life-ring"></i> Tickets</a>
                <a href="<?php echo $baseUrl; ?>/admin/clientes" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/clientes') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-address-book"></i> Clientes</a>
                <a href="<?php echo $baseUrl; ?>/admin/notificaciones" class="sidebar__link sidebar__link--with-badge <?php echo $currentPath === '/admin/notificaciones' ? 'sidebar__link--active' : ''; ?>"><span class="sidebar__link-content"><i class="fas fa-bell"></i> Notificaciones</span><span class="sidebar__badge" data-notification-badge <?php echo $notificationContext['unread'] > 0 ? '' : 'hidden'; ?>><?php echo $notificationContext['unread'] > 0 ? (int) $notificationContext['unread'] : ''; ?></span></a>
                <a href="<?php echo $baseUrl; ?>/admin/google-calendar" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/google-calendar') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Google Calendar</a>
                <?php if ($adminRole === 'admin'): ?><a href="<?php echo $baseUrl; ?>/admin/usuarios" class="sidebar__link <?php echo str_starts_with($currentPath, '/admin/usuarios') ? 'sidebar__link--active' : ''; ?>"><i class="fas fa-users-cog"></i> Usuarios</a><?php endif; ?>
                <a href="<?php echo $baseUrl; ?>/admin/logout" class="sidebar__link sidebar__link--logout"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            </nav>
        </aside>
        <main class="admin-main"><?php echo $content; ?></main>
    </div>
    <script src="<?php echo $baseUrl; ?>/js/main.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
