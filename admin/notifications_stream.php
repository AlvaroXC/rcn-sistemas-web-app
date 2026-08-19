<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';

ignore_user_abort(true);
set_time_limit(0);

function notification_stream_start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_cache_limiter('');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function notification_stream_get_snapshot() {
    notification_stream_start_session();

    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
    $lastActivity = isset($_SESSION['last_activity']) ? (int) $_SESSION['last_activity'] : 0;
    $isExpired = $lastActivity > 0 && (time() - $lastActivity > ADMIN_SESSION_TIMEOUT);
    $isValid = $adminId > 0 && !$isExpired;

    if ($isValid) {
        session_write_close();
    } else {
        $_SESSION = [];
        session_destroy();
    }

    return [
        'valid' => $isValid,
        'admin_id' => $adminId,
    ];
}

function notification_stream_emit($eventName, array $payload, $eventId = null) {
    if ($eventId !== null) {
        echo 'id: ' . (int) $eventId . "\n";
    }

    echo 'event: ' . $eventName . "\n";
    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

    @ob_flush();
    @flush();
}

function notification_stream_emit_keepalive() {
    echo ": keepalive\n\n";
    @ob_flush();
    @flush();
}

$snapshot = notification_stream_get_snapshot();

header('Content-Type: text/event-stream; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Accel-Buffering: no');

if (!$snapshot['valid']) {
    notification_stream_emit('session_expired', [
        'message' => 'La sesión del panel ya no es válida.',
        'login_url' => 'login.php',
    ]);
    exit;
}

$adminId = (int) $snapshot['admin_id'];
$requestedLastId = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;
$headerLastId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? (int) $_SERVER['HTTP_LAST_EVENT_ID'] : 0;
$lastNotificationId = max(0, $requestedLastId, $headerLastId);
$startedAt = time();
$keepaliveAt = 0;

while (!connection_aborted() && (time() - $startedAt) < 55) {
    $snapshot = notification_stream_get_snapshot();
    if (!$snapshot['valid'] || (int) $snapshot['admin_id'] !== $adminId) {
        notification_stream_emit('session_expired', [
            'message' => 'La sesión del panel expiró por inactividad.',
            'login_url' => 'login.php',
        ]);
        break;
    }

    $result = get_ticket_assignment_notifications_after_id($conn, $adminId, $lastNotificationId, 20);
    if ($result) {
        while ($notification = $result->fetch_assoc()) {
            $notificationId = (int) ($notification['id'] ?? 0);
            if ($notificationId <= 0) {
                continue;
            }

            $lastNotificationId = $notificationId;
            $unreadCount = get_unread_ticket_assignment_notification_count($conn, $adminId);

            notification_stream_emit('ticket_assignment', [
                'notification_id' => $notificationId,
                'ticket_id' => (int) ($notification['ticket_id'] ?? 0),
                'type' => (string) ($notification['type'] ?? 'ticket_assignment'),
                'title' => (string) ($notification['title'] ?? ''),
                'message' => (string) ($notification['message'] ?? ''),
                'unread_count' => $unreadCount,
                'created_at' => (string) ($notification['created_at'] ?? ''),
                'ticket_url' => 'ticket_view.php?id=' . (int) ($notification['ticket_id'] ?? 0) . '&return=' . urlencode('notifications.php'),
            ], $notificationId);
        }
    }

    if ((time() - $keepaliveAt) >= 15) {
        notification_stream_emit_keepalive();
        $keepaliveAt = time();
    }

    sleep(1);
}
