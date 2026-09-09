<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\NotificationRepository;
use RcnSistemas\Repositories\PushDeviceRepository;
use RcnSistemas\Repositories\PushPreferenceRepository;
use RcnSistemas\Core\Routing\Url;

final class NotificationController extends Controller
{
    public function __construct(private readonly NotificationRepository $notifications, private readonly PushDeviceRepository $devices, private readonly PushPreferenceRepository $preferences, private readonly array $firebase, ViewRenderer $views) { parent::__construct($views); }
    public function index(Request $request, int $userId): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->input('action', 'register');
            if ($action === 'register') {
                $token = trim((string) $request->input('token', ''));
                if ($token === '' || strlen($token) > 4096) return Response::json(['error' => 'El dispositivo no es válido.'], 422);
                $this->devices->upsert($userId, $token, trim((string) $request->input('device_key', '')) ?: null, trim((string) $request->input('platform', 'web')) ?: 'web', substr(trim((string) ($request->server('HTTP_USER_AGENT', '') ?? '')), 0, 512));
                return Response::json(['ok' => true]);
            }
            if ($action === 'deactivate') $this->devices->deactivateById($userId, (int) $request->input('device_id', 0));
            if ($action === 'preferences') $this->preferences->save($userId, ['assignments' => $request->input('assignments'), 'observations' => $request->input('observations'), 'status_changes' => $request->input('status_changes')]);
            return Response::redirect(Url::to('/admin/notificaciones'));
        }
        return $this->view('admin.notifications.index', ['pageTitle' => 'Notificaciones', 'notifications' => $this->notifications->recent($userId), 'unreadCount' => $this->notifications->unreadCount($userId), 'devices' => $this->devices->allForUser($userId), 'preferences' => $this->preferences->get($userId), 'pushConfigured' => !empty($this->firebase['enabled']) && !empty($this->firebase['project_id']) && !empty($this->firebase['vapid_public_key'])], 'layouts.admin');
    }
}
