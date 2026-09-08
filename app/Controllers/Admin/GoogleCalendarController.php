<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Services\Google\GoogleCalendarService;

final class GoogleCalendarController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarConnectionRepository $connections,
        private readonly GoogleCalendarService $calendar,
        private readonly CsrfService $csrf,
        private readonly SessionManager $session,
        ViewRenderer $views
    ) { parent::__construct($views); }

    public function index(Request $request): Response
    {
        $userId = $this->session->userId();
        $error = (string) $request->query('error', ''); $message = $request->query('connected') ? 'Google Calendar fue conectado correctamente.' : null;
        if ($request->isMethod('POST') && $request->input('action') === 'disconnect') {
            try { $this->csrf->validate((string) $request->input('_csrf_token', '')); $connection = $this->connections->findByUserId($userId); if ($connection) { try { $this->calendar->revoke($connection); } catch (\Throwable) {} } $this->connections->disconnect($userId); $message = 'Google Calendar fue desconectado.'; }
            catch (\Throwable $exception) { $error = $exception->getMessage(); }
        }
        $connection = $this->connections->findByUserId($userId);
        return $this->view('admin.google-calendar.index', ['pageTitle' => 'Google Calendar', 'connection' => $connection, 'error' => $error, 'message' => $message, 'csrf' => $this->csrf->token()], 'layouts.admin');
    }

    public function connect(): Response
    {
        if (!$this->session->isAuthenticated()) return Response::redirect(Url::to('/admin/login'));
        $state = bin2hex(random_bytes(32)); $_SESSION['google_calendar_oauth_state'] = $state;
        return Response::redirect($this->calendar->authorizationUrl($state));
    }

    public function callback(Request $request): Response
    {
        if (!$this->session->isAuthenticated()) return Response::redirect(Url::to('/admin/login'));
        $state = (string) $request->query('state', ''); $expected = (string) ($_SESSION['google_calendar_oauth_state'] ?? '');
        unset($_SESSION['google_calendar_oauth_state']);
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) return $this->index($request->withQuery(['error' => 'La autorización de Google no es válida.']));
        if ($request->query('error')) return Response::redirect(Url::to('/admin/google-calendar') . '?error=cancelled');
        try {
            $tokens = $this->calendar->exchangeCode((string) $request->query('code', ''));
            $this->connections->save(['user_id' => $this->session->userId(), ...$tokens['connection']]);
            return Response::redirect(Url::to('/admin/google-calendar') . '?connected=1');
        } catch (\Throwable $exception) { return Response::redirect(Url::to('/admin/google-calendar') . '?error=' . rawurlencode($exception->getMessage())); }
    }
}
