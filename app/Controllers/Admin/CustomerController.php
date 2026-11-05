<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Repositories\CustomerQueryRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Customers\CustomerCatalog;
use RcnSistemas\Services\Licenses\LicenseNotice;

final class CustomerController extends Controller
{
    private CustomerService $service;
    public function __construct(private readonly CustomerRepository $customers, private readonly CustomerQueryRepository $customerQuery, private readonly CustomerLicenseRepository $licenses, ViewRenderer $views)
    {
        parent::__construct($views);
        $this->service = new CustomerService($customers, new CustomerIdentity());
    }

    public function index(Request $request): Response
    {
        $customerId = (int) ($request->query('id', $request->input('id', 0)));
        $editing = $customerId > 0 || $request->query('action') === 'new';
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null;
        if ($customerId > 0 && !$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        $error = null;
        if ($request->isMethod('POST')) {
            try {
                $customer = $this->service->save(['id' => $customerId, 'name' => $request->input('name', ''), 'phone' => $request->input('phone', ''), 'email' => $request->input('email'), 'business_name' => $request->input('business_name', ''), 'rfc' => $request->input('rfc', ''), 'customer_type' => $request->input('customer_type', 'persona_fisica')], true);
                return Response::redirect(Url::to('/admin/clientes/' . $customer->id));
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) $perPage = 10;
        $total = $this->customerQuery->count($search);
        return $this->view('admin.customers.index', ['pageTitle' => 'Clientes', 'customer' => $customer, 'editing' => $editing, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'customers' => $this->customerQuery->list($search, $perPage, ($page - 1) * $perPage), 'search' => $search, 'page' => $page, 'perPage' => $perPage, 'total' => $total, 'totalPages' => max(1, (int) ceil($total / $perPage)), 'customerTypes' => CustomerCatalog::labels()], 'layouts.admin');
    }

    public function show(Request $request): Response
    {
        $customerId = (int) $request->query('id', 0);
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null;
        if (!$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        $ticketFilter = $customer->phone ?: ($customer->email ?: $customer->name);
        return $this->view('admin.customers.show', ['pageTitle' => 'Cliente: ' . $customer->name, 'customer' => $customer, 'tickets' => $this->customerQuery->tickets($customerId), 'licenses' => $this->licenses->forCustomer($customerId), 'licenseNotice' => LicenseNotice::pull(), 'today' => date('Y-m-d'), 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'ticketFilter' => $ticketFilter, 'customerTypeLabel' => CustomerCatalog::label($customer->customerType)], 'layouts.admin');
    }
}
