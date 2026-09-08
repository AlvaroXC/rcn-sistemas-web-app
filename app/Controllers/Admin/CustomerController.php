<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerController extends Controller
{
    private CustomerService $service;
    public function __construct(private readonly CustomerRepository $customers, ViewRenderer $views) { parent::__construct($views); $this->service = new CustomerService($customers, new CustomerIdentity()); }

    public function index(Request $request): Response
    {
        $customerId = (int) ($request->query('id', $request->input('id', 0))); $editing = $customerId > 0 || $request->query('action') === 'new';
        $customer = $customerId > 0 ? $this->customers->find($customerId) : null; if ($customerId > 0 && !$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        $error = null;
        if ($request->isMethod('POST')) {
            try { $customer = $this->service->save(['name' => $request->input('name', ''), 'phone' => $request->input('phone', ''), 'email' => $request->input('email'), 'business_name' => $request->input('business_name', ''), 'customer_type' => $request->input('customer_type', 'persona_fisica')]); return Response::redirect('customers.php?id=' . $customer->id); }
            catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        }
        return $this->view('admin.customers.index', ['pageTitle' => 'Clientes', 'customer' => $customer, 'editing' => $editing, 'error' => $error], 'layouts.admin');
    }
}
