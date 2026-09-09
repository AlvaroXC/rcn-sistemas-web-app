<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Repositories\CustomerQueryRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerController extends Controller
{
    private CustomerService $service;
    public function __construct(private readonly CustomerRepository $customers, private readonly CustomerQueryRepository $customerQuery, ViewRenderer $views) { parent::__construct($views); $this->service = new CustomerService($customers, new CustomerIdentity()); }

    public function index(Request $request): Response
    {
        $customerId = (int) ($request->query('id', $request->input('id', 0))); $editing = $customerId > 0 || $request->query('action') === 'new';
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null; if ($customerId > 0 && !$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        $error = null;
        if ($request->isMethod('POST')) {
            try { $customer = $this->service->save(['id' => $customerId, 'name' => $request->input('name', ''), 'phone' => $request->input('phone', ''), 'email' => $request->input('email'), 'business_name' => $request->input('business_name', ''), 'customer_type' => $request->input('customer_type', 'persona_fisica')]); return Response::redirect(Url::to('/admin/clientes/' . $customer->id)); }
            catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        }
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) $perPage = 10;
        $total = $this->customerQuery->count($search);
        return $this->view('admin.customers.index', ['pageTitle' => 'Clientes', 'customer' => $customer, 'editing' => $editing, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'customers' => $this->customerQuery->list($search, $perPage, ($page - 1) * $perPage), 'search' => $search, 'page' => $page, 'perPage' => $perPage, 'total' => $total, 'totalPages' => max(1, (int) ceil($total / $perPage))], 'layouts.admin');
    }

    public function show(Request $request): Response
    {
        $customerId = (int) $request->query('id', 0);
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null;
        if (!$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        $returnUrl = $this->returnUrl((string) $request->query('return', Url::to('/admin/tickets')));
        $ticketFilter = $customer->phone ?: ($customer->email ?: $customer->name);
        return $this->view('admin.customers.show', ['pageTitle' => 'Cliente: ' . $customer->name, 'customer' => $customer, 'tickets' => $this->customerQuery->tickets($customerId), 'returnUrl' => $returnUrl, 'ticketFilter' => $ticketFilter], 'layouts.admin');
    }

    private function returnUrl(string $url): string
    {
        $url = trim($url);
        $basePath = rtrim((string) (parse_url((string) (function_exists('getEnvVar') ? getEnvVar('APP_URL', '') : ''), PHP_URL_PATH) ?: ($_SERVER['RCN_BASE_PATH'] ?? '')), '/');
        if ($basePath !== '' && str_starts_with($url, $basePath . '/')) $url = substr($url, strlen($basePath));

        return preg_match('#^/admin/(tickets|notificaciones|clientes)(?:\\?[^\\r\\n]*)?$#', $url) ? Url::to($url) : Url::to('/admin/tickets');
    }
}
