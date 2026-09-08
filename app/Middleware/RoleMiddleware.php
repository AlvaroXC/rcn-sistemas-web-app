<?php

namespace RcnSistemas\Middleware;

use Closure;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Security\SessionManager;

final class RoleMiddleware
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly string $requiredRole = 'admin'
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->session->isAuthenticated()) {
            return Response::redirect(\RcnSistemas\Core\Routing\Url::to('/admin/login'));
        }

        if ($this->session->role() !== $this->requiredRole) {
            return Response::html('No tienes permisos para acceder a este recurso.', 403);
        }

        return $next($request);
    }
}
