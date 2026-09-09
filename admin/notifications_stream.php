<?php

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliNotificationRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
header('Content-Type: text/event-stream; charset=UTF-8'); header('Cache-Control: no-cache, no-store, must-revalidate'); header('X-Accel-Buffering: no');
$emit = static function (string $event, array $payload, ?int $id = null): void { if ($id !== null) echo 'id: ' . $id . "\n"; echo 'event: ' . $event . "\n" . 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"; @ob_flush(); @flush(); };
if (!$session->isAuthenticated()) { $emit('session_expired', ['message' => 'La sesión del panel ya no es válida.', 'login_url' => \RcnSistemas\Core\Routing\Url::to('/admin/login')]); exit; }
$repository = new MysqliNotificationRepository($application['database']->get()); $lastId = max((int) ($_GET['last_id'] ?? 0), (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0)); $startedAt = time();
while (!connection_aborted() && time() - $startedAt < 55) {
    foreach ($repository->after($session->userId(), $lastId) as $notification) { $lastId = (int) $notification['id']; $emit('ticket_assignment', ['notification_id' => $lastId, 'ticket_id' => (int) $notification['ticket_id'], 'type' => $notification['type'], 'title' => $notification['title'], 'message' => $notification['message'], 'unread_count' => $repository->unreadCount($session->userId()), 'created_at' => $notification['created_at'], 'ticket_url' => \RcnSistemas\Core\Routing\Url::to('/admin/tickets/' . (int) $notification['ticket_id'])], $lastId); }
    echo ": keepalive\n\n"; @ob_flush(); @flush(); sleep(1);
}
