<?php

use RcnSistemas\Controllers\Admin\AuthController;
use RcnSistemas\Controllers\Admin\CustomerController;
use RcnSistemas\Controllers\Admin\CustomerSearchController;
use RcnSistemas\Controllers\Admin\DashboardController;
use RcnSistemas\Controllers\Admin\GoogleCalendarController;
use RcnSistemas\Controllers\Admin\NotificationController;
use RcnSistemas\Controllers\Admin\ProductController as AdminProductController;
use RcnSistemas\Controllers\Admin\PromotionController as AdminPromotionController;
use RcnSistemas\Controllers\Admin\TicketController;
use RcnSistemas\Controllers\Admin\TicketListController;
use RcnSistemas\Controllers\Admin\UserController;
use RcnSistemas\Controllers\Public\ContactController;
use RcnSistemas\Controllers\Public\HomeController;
use RcnSistemas\Controllers\Public\ProductController;
use RcnSistemas\Controllers\Public\PromotionController;
use RcnSistemas\Controllers\Public\TestimonialController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Router;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliAdminUserManagementRepository;
use RcnSistemas\Repositories\MysqliAdminUserRepository;
use RcnSistemas\Repositories\MysqliCustomerQueryRepository;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliCustomerSearchRepository;
use RcnSistemas\Repositories\MysqliDashboardMetricsRepository;
use RcnSistemas\Repositories\MysqliNotificationRepository;
use RcnSistemas\Repositories\MysqliPushDeviceRepository;
use RcnSistemas\Repositories\MysqliPushPreferenceRepository;
use RcnSistemas\Repositories\MysqliGoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\MysqliTicketCalendarRepository;
use RcnSistemas\Repositories\MysqliProductRepository;
use RcnSistemas\Repositories\MysqliPromotionManagementRepository;
use RcnSistemas\Repositories\MysqliPromotionRepository;
use RcnSistemas\Repositories\MysqliTestimonialRepository;
use RcnSistemas\Repositories\MysqliTicketDetailRepository;
use RcnSistemas\Repositories\MysqliTicketQueryRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Services\AdminUserService;
use RcnSistemas\Services\AuthService;
use RcnSistemas\Services\Communications\ContactService;
use RcnSistemas\Services\Communications\MailService;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\ProductService;
use RcnSistemas\Services\PromotionManagementService;
use RcnSistemas\Services\PromotionService;
use RcnSistemas\Services\TestimonialService;
use RcnSistemas\Services\Tickets\TicketListService;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;
use RcnSistemas\Services\Google\GoogleCalendarService;
use RcnSistemas\Services\Google\TokenEncryption;
use RcnSistemas\Services\Google\GoogleCalendarAppointmentSyncService;
use RcnSistemas\Services\Notifications\FcmClient;
use RcnSistemas\Services\Notifications\PushDeliveryService;
use RcnSistemas\Services\Notifications\TicketNotificationDispatcher;

return static function (array $application): Router {
    $connection = $application['database']->get();
    $views = new ViewRenderer($application['root'] . '/app/Views');
    $session = new SessionManager();
    $session->start();
    $csrf = new CsrfService($session);
    $router = new Router();

    $router->get('/firebase-config.json', static fn (): Response => Response::json($application['config']->get('firebase.web_config', []), 200));

    $authorized = static function (callable $handler, bool $adminOnly = false) use ($session, $csrf): callable {
        return static function (Request $request) use ($handler, $adminOnly, $session, $csrf): Response {
            if (!$session->isAuthenticated()) {
                return Response::redirect(Url::to('/admin/login'));
            }
            if ($adminOnly && $session->role() !== 'admin') {
                return Response::html('No tienes permisos para acceder a este recurso.', 403);
            }
            $session->touch();
            $csrf->token();
            if ($request->isMethod('POST')) {
                $csrf->validate($request->input('_csrf_token'));
            }

            return $handler($request, $session);
        };
    };

    $products = new MysqliProductRepository($connection);
    $promotions = new MysqliPromotionRepository($connection);
    $router->get('/', static fn (): Response => (new HomeController($products, $views))->index());
    $router->get('/productos', static fn (): Response => (new ProductController($products, $views))->index());
    $router->get('/productos/{id}', static fn (Request $request): Response => (new ProductController($products, $views))->show((int) $request->query('id')));
    $router->get('/promociones', static fn (): Response => (new PromotionController($promotions, $views))->index());
    $router->get('/promociones/{id}', static fn (Request $request): Response => (new PromotionController($promotions, $views))->show((int) $request->query('id')));

    $testimonials = new TestimonialController(new MysqliTestimonialRepository($connection), new TestimonialService(), $views);
    $router->get('/testimoniales', static fn (Request $request): Response => $testimonials->index($request));
    $router->post('/testimoniales', static fn (Request $request): Response => $testimonials->index($request));

    $contactCustomers = new MysqliCustomerRepository($connection);
    $contact = new ContactController(
        new ContactService(
            new CustomerService($contactCustomers, new CustomerIdentity()),
            new TicketService(new MysqliTicketRepository($connection), new TicketWorkflow()),
            new UploadService(),
            new MailService($application['config']->get('mail', []))
        ),
        $csrf,
        new UploadService(),
        $application['root'] . '/uploads/tickets',
        $views,
        $products,
        $promotions
    );
    $router->get('/contacto', static fn (Request $request): Response => $contact->index($request));
    $router->post('/contacto', static fn (Request $request): Response => $contact->index($request));
    $router->get('/contacto/producto/{id}', static fn (Request $request): Response => $contact->index($request->withQuery(['product' => $request->query('id')])));
    $router->get('/contacto/promocion/{id}', static fn (Request $request): Response => $contact->index($request->withQuery(['promo' => $request->query('id')])));
    $router->post('/contacto/producto/{id}', static fn (Request $request): Response => $contact->index($request->withQuery(['product' => $request->query('id')])));
    $router->post('/contacto/promocion/{id}', static fn (Request $request): Response => $contact->index($request->withQuery(['promo' => $request->query('id')])));

    $auth = new AuthController(new AuthService(new MysqliAdminUserRepository($connection), $session), $csrf, $session, $views);
    $router->get('/admin/login', static fn (Request $request): Response => $auth->login($request));
    $router->post('/admin/login', static fn (Request $request): Response => $auth->login($request));
    $router->get('/admin/logout', static fn (): Response => $auth->logout());

    $dashboard = new DashboardController(new MysqliDashboardMetricsRepository($connection), $views);
    $router->get('/admin', $authorized(static fn (Request $request, SessionManager $currentSession): Response => $dashboard->index($currentSession->userId(), $currentSession->role())));

    $ticketList = new TicketListController(new MysqliTicketQueryRepository($connection), new TicketListService(), $views);
    $router->get('/admin/tickets', $authorized(static fn (Request $request, SessionManager $currentSession): Response => $ticketList->index($request, $currentSession->userId(), $currentSession->role())));

    $ticketCalendarConnections = new MysqliGoogleCalendarConnectionRepository($connection);
    $ticketCalendarClient = new GoogleCalendarService($application['config']->get('google', []), $ticketCalendarConnections, new TokenEncryption((string) $application['config']->get('app.encryption_key', '')));
    $ticketCalendarSync = new GoogleCalendarAppointmentSyncService($ticketCalendarConnections, new MysqliTicketCalendarRepository($connection), $ticketCalendarClient, $application['config']->get('google', []));
    $pushDevices = new MysqliPushDeviceRepository($connection);
    $pushPreferences = new MysqliPushPreferenceRepository($connection);
    $pushDelivery = new PushDeliveryService($pushDevices, $pushPreferences, new FcmClient($application['config']->get('firebase', [])));
    $ticketNotifications = new TicketNotificationDispatcher($connection, $pushDelivery, (string) $application['config']->get('app.url', ''));
    $ticketController = new TicketController(new MysqliTicketRepository($connection), new MysqliCustomerRepository($connection), $views, new MysqliTicketDetailRepository($connection), new MysqliCustomerSearchRepository($connection), new UploadService(), $application['root'] . '/uploads/tickets', new MysqliAdminUserManagementRepository($connection), $ticketCalendarSync, $ticketNotifications);
    $createTicket = $authorized(static fn (Request $request, SessionManager $currentSession): Response => $ticketController->create($request, $currentSession->userId(), $currentSession->role()));
    $showTicket = $authorized(static fn (Request $request, SessionManager $currentSession): Response => $ticketController->show($request, $currentSession->userId(), $currentSession->role()));
    $router->get('/admin/tickets/nuevo', $createTicket);
    $router->post('/admin/tickets/nuevo', $createTicket);
    $createTicketForCustomer = $authorized(static fn (Request $request, SessionManager $currentSession): Response => $ticketController->create($request->withQuery(['customer_id' => $request->query('id')]), $currentSession->userId(), $currentSession->role()));
    $router->get('/admin/tickets/nuevo/cliente/{id}', $createTicketForCustomer);
    $router->post('/admin/tickets/nuevo/cliente/{id}', $createTicketForCustomer);
    $router->get('/admin/tickets/{id}', $showTicket);
    $router->post('/admin/tickets/{id}', $showTicket);

    $customerController = new CustomerController(new MysqliCustomerRepository($connection), new MysqliCustomerQueryRepository($connection), $views);
    $customers = $authorized(static fn (Request $request): Response => $customerController->index($request));
    $router->get('/admin/clientes', $customers);
    $router->post('/admin/clientes', $customers);
    $router->get('/admin/clientes/nuevo', $authorized(static fn (Request $request): Response => $customerController->index($request->withQuery(['action' => 'new']))));
    $router->post('/admin/clientes/nuevo', $authorized(static fn (Request $request): Response => $customerController->index($request->withQuery(['action' => 'new']))));
    $router->get('/admin/clientes/{id}/editar', $authorized(static fn (Request $request): Response => $customerController->index($request)));
    $router->post('/admin/clientes/{id}/editar', $authorized(static fn (Request $request): Response => $customerController->index($request)));
    $router->get('/admin/clientes/{id}', $authorized(static fn (Request $request): Response => $customerController->show($request)));
    $router->get('/admin/clientes/buscar', $authorized(static fn (Request $request): Response => (new CustomerSearchController(new MysqliCustomerSearchRepository($connection)))->index($request)));

    $adminProducts = new AdminProductController($products, new ProductService($products), $views, new UploadService(), $application['root'] . '/uploads/images', $application['root'] . '/uploads/pdfs');
    $productIndex = $authorized(static fn (Request $request): Response => $adminProducts->index($request));
    $router->get('/admin/productos', $productIndex);
    $router->post('/admin/productos', $productIndex);
    $router->get('/admin/productos/nuevo', $authorized(static fn (Request $request): Response => $adminProducts->index($request->withQuery(['action' => 'new']))));
    $router->post('/admin/productos/nuevo', $authorized(static fn (Request $request): Response => $adminProducts->index($request->withQuery(['action' => 'new']))));
    $router->get('/admin/productos/{id}/editar', $authorized(static fn (Request $request): Response => $adminProducts->index($request->withQuery(['action' => 'edit']))));
    $router->post('/admin/productos/{id}/editar', $authorized(static fn (Request $request): Response => $adminProducts->index($request->withQuery(['action' => 'edit']))));

    $managedPromotions = new MysqliPromotionManagementRepository($connection);
    $adminPromotions = new AdminPromotionController($managedPromotions, new PromotionManagementService($managedPromotions, new PromotionService()), new UploadService(), $views);
    $promotionIndex = $authorized(static fn (Request $request): Response => $adminPromotions->index($request));
    $router->get('/admin/promociones', $promotionIndex);
    $router->post('/admin/promociones', $promotionIndex);
    $router->get('/admin/promociones/nueva', $authorized(static fn (Request $request): Response => $adminPromotions->index($request->withQuery(['action' => 'new']))));
    $router->post('/admin/promociones/nueva', $authorized(static fn (Request $request): Response => $adminPromotions->index($request->withQuery(['action' => 'new']))));
    $router->get('/admin/promociones/{id}/editar', $authorized(static fn (Request $request): Response => $adminPromotions->index($request->withQuery(['action' => 'edit']))));
    $router->post('/admin/promociones/{id}/editar', $authorized(static fn (Request $request): Response => $adminPromotions->index($request->withQuery(['action' => 'edit']))));

    $notifications = new NotificationController(new MysqliNotificationRepository($connection), new MysqliPushDeviceRepository($connection), new MysqliPushPreferenceRepository($connection), $application['config']->get('firebase', []), $views);
    $router->get('/admin/notificaciones', $authorized(static fn (Request $request, SessionManager $currentSession): Response => $notifications->index($request, $currentSession->userId())));
    $router->post('/admin/notificaciones', $authorized(static fn (Request $request, SessionManager $currentSession): Response => $notifications->index($request, $currentSession->userId())));

    $googleConnections = new MysqliGoogleCalendarConnectionRepository($connection);
    $googleCalendar = new GoogleCalendarController($googleConnections, new GoogleCalendarService($application['config']->get('google', []), $googleConnections, new TokenEncryption((string) $application['config']->get('app.encryption_key', ''))), $csrf, $session, $views);
    $router->get('/admin/google-calendar', $authorized(static fn (Request $request): Response => $googleCalendar->index($request)));
    $router->post('/admin/google-calendar', $authorized(static fn (Request $request): Response => $googleCalendar->index($request)));
    $router->get('/admin/google-calendar/connect', $authorized(static fn (): Response => $googleCalendar->connect()));
    $router->get('/admin/google-calendar/callback', $authorized(static fn (Request $request): Response => $googleCalendar->callback($request)));

    $usersRepository = new MysqliAdminUserManagementRepository($connection);
    $users = new UserController($usersRepository, new AdminUserService($usersRepository), $views);
    $usersIndex = $authorized(static fn (Request $request): Response => $users->index($request), true);
    $router->get('/admin/usuarios', $usersIndex);
    $router->post('/admin/usuarios', $usersIndex);
    $router->get('/admin/usuarios/nuevo', $authorized(static fn (Request $request): Response => $users->index($request->withQuery(['action' => 'new'])), true));
    $router->post('/admin/usuarios/nuevo', $authorized(static fn (Request $request): Response => $users->index($request->withQuery(['action' => 'new'])), true));
    $router->get('/admin/usuarios/{id}/editar', $authorized(static fn (Request $request): Response => $users->index($request), true));
    $router->post('/admin/usuarios/{id}/editar', $authorized(static fn (Request $request): Response => $users->index($request), true));

    return $router;
};
