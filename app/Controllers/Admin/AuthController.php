<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly CsrfService $csrf,
        private readonly SessionManager $session,
        ViewRenderer $views
    ) {
        parent::__construct($views);
    }

    public function login(Request $request): Response
    {
        if ($this->session->isAuthenticated()) {
            return Response::redirect(Url::to('/admin/'));
        }

        $error = null;
        if ($request->isMethod('POST')) {
            try {
                $this->csrf->validate((string) $request->input('_csrf_token', ''));
                if ($this->auth->authenticate(trim((string) $request->input('username', '')), (string) $request->input('password', ''), (bool) $request->input('remember_me', false))) {
                    return Response::redirect(Url::to('/admin/'));
                }
                $error = 'Usuario o contraseña incorrectos.';
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->view('admin.auth.login', ['csrf' => $this->csrf->token(), 'error' => $error]);
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return Response::redirect(Url::to('/admin/login'));
    }
}
