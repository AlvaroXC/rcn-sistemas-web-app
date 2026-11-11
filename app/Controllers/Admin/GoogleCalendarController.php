<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\GoogleCalendarIntegrationRepository;
use RcnSistemas\Services\Google\GoogleCalendarConfigurationException;
use RcnSistemas\Services\Google\GoogleCalendarGateway;

final class GoogleCalendarController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarIntegrationRepository $integrations,
        private readonly GoogleCalendarGateway $calendar,
        private readonly CsrfService $csrf,
        private readonly SessionManager $session,
        ViewRenderer $views
    ) { parent::__construct($views); }

    public function index(Request $request): Response
    {
        if (!$this->session->isAuthenticated()) return Response::redirect(Url::to('/admin/login'));
        if ($request->isMethod('POST') && $this->session->role() !== 'admin') return Response::html('No tienes permisos para administrar Google Calendar.', 403);

        $error = (string) $request->query('error', '');
        $message = $request->query('connected') ? 'El calendario común fue conectado correctamente.' : null;
        if ($request->isMethod('POST') && $request->input('action') === 'disconnect') {
            try {
                $this->csrf->validate((string) $request->input('_csrf_token', ''));
                $integration = $this->integrations->findShared();
                if ($integration) {
                    try { $this->calendar->revoke($integration); } catch (\Throwable) {}
                }
                $this->integrations->disconnectShared();
                $message = 'El calendario común fue desconectado.';
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->view('admin.google-calendar.index', [
            'pageTitle' => 'Google Calendar',
            'connection' => $this->integrations->findShared(),
            'error' => $error,
            'message' => $message,
            'csrf' => $this->csrf->token(),
            'isAdmin' => $this->session->role() === 'admin',
        ], 'layouts.admin');
    }

    public function connect(): Response
    {
        if (!$this->session->isAuthenticated()) return Response::redirect(Url::to('/admin/login'));
        if ($this->session->role() !== 'admin') return Response::html('No tienes permisos para administrar Google Calendar.', 403);
        $state = bin2hex(random_bytes(32));
        try {
            $authorizationUrl = $this->calendar->authorizationUrl($state);
        } catch (GoogleCalendarConfigurationException $exception) {
            return Response::redirect(Url::to('/admin/google-calendar') . '?' . http_build_query(['error' => $exception->getMessage()]));
        }
        $_SESSION['google_calendar_oauth_state'] = $state;
        return Response::redirect($authorizationUrl);
    }

    public function callback(Request $request): Response
    {
        if (!$this->session->isAuthenticated()) return Response::redirect(Url::to('/admin/login'));
        if ($this->session->role() !== 'admin') return Response::html('No tienes permisos para administrar Google Calendar.', 403);

        $state = (string) $request->query('state', '');
        $expected = (string) ($_SESSION['google_calendar_oauth_state'] ?? '');
        unset($_SESSION['google_calendar_oauth_state']);
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            return Response::redirect(Url::to('/admin/google-calendar') . '?error=invalid_state');
        }
        if ($request->query('error')) return Response::redirect(Url::to('/admin/google-calendar') . '?error=cancelled');

        try {
            $tokens = $this->calendar->exchangeCode((string) $request->query('code', ''));
            if (!$this->integrations->saveShared($tokens['connection'])) {
                throw new \RuntimeException('No se pudo guardar la conexión del calendario común.');
            }
            return Response::redirect(Url::to('/admin/google-calendar') . '?connected=1');
        } catch (\Throwable $exception) {
            return Response::redirect(Url::to('/admin/google-calendar') . '?error=' . rawurlencode($exception->getMessage()));
        }
    }
}
