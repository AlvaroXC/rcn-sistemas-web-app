<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\AdminUserManagementRepository;
use RcnSistemas\Services\AdminUserService;

final class UserController extends Controller
{
    public function __construct(private readonly AdminUserManagementRepository $users, private readonly AdminUserService $service, ViewRenderer $views) { parent::__construct($views); }

    public function index(Request $request): Response
    {
        $userId = (int) ($request->query('id', $request->input('id', 0)));
        $editing = $userId > 0 || $request->query('action') === 'new';
        $user = $userId > 0 ? $this->users->findById($userId) : null;
        $error = null;
        if ($editing && $userId > 0 && !$user) return $this->view('errors.404', [], 'layouts.admin', 404);
        if ($request->isMethod('POST')) {
            try {
                $this->service->save(['username' => $request->input('username', ''), 'display_name' => $request->input('display_name', ''), 'role' => $request->input('role', 'agent'), 'active' => $request->input('active') !== null ? 1 : 0, 'password' => $request->input('password', '')], $userId);
                return Response::redirect(Url::to('/admin/usuarios'));
            } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        }
        return $this->view('admin.users.index', ['pageTitle' => 'Usuarios administrativos', 'users' => $editing ? [] : $this->users->all(), 'user' => $user, 'editing' => $editing, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin');
    }
}
