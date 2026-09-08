<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\NotificationRepository;

final class NotificationController extends Controller
{
    public function __construct(private readonly NotificationRepository $notifications, ViewRenderer $views) { parent::__construct($views); }
    public function index(Request $request, int $userId): Response { return $this->view('admin.notifications.index', ['pageTitle' => 'Notificaciones', 'notifications' => $this->notifications->recent($userId), 'unreadCount' => $this->notifications->unreadCount($userId)], 'layouts.admin'); }
}
