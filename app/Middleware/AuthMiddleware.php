<?php

namespace RcnSistemas\Middleware;

use Closure;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Security\SessionManager;

final class AuthMiddleware
{
    public function __construct(private readonly SessionManager $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->session->isAuthenticated()) {
            return Response::redirect(\RcnSistemas\Core\Routing\Url::to('/admin/login'));
        }

        $this->session->touch();

        return $next($request);
    }
}
