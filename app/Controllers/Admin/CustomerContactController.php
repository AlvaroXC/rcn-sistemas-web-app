<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Models\Customer;
use RcnSistemas\Models\CustomerContact;
use RcnSistemas\Repositories\CustomerContactRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\ContactNotice;
use RcnSistemas\Services\Customers\CustomerContactService;

final class CustomerContactController extends Controller
{
    private CustomerContactService $service;

    public function __construct(
        private readonly CustomerContactRepository $contacts,
        private readonly CustomerRepository $customers,
        ViewRenderer $views
    ) {
        parent::__construct($views);
        $this->service = new CustomerContactService($contacts, $customers);
    }

    public function create(Request $request): Response
    {
        $customer = $this->customers->find((int) $request->query('id', 0));
        if (!$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $customer, null);
    }

    public function edit(Request $request): Response
    {
        $contact = $this->contacts->find((int) $request->query('id', 0));
        $customer = $contact ? $this->customers->find($contact->customerId) : null;
        if (!$contact || !$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $customer, $contact);
    }

    public function delete(Request $request): Response
    {
        $contact = $this->contacts->find((int) $request->query('id', 0));
        if (!$contact) return $this->view('errors.404', [], 'layouts.admin', 404);
        $this->contacts->delete($contact->id);
        ContactNotice::push('Se eliminó el contacto ' . $contact->name . '.');
        return Response::redirect(Url::to('/admin/clientes/' . $contact->customerId));
    }

    private function form(Request $request, Customer $customer, ?CustomerContact $contact): Response
    {
        $error = null;
        $values = ['name' => $contact?->name ?? '', 'phone' => $contact?->phone ?? '', 'email' => $contact?->email ?? ''];
        if ($request->isMethod('POST')) {
            foreach (array_keys($values) as $field) $values[$field] = (string) $request->input($field, '');
            try {
                $saved = $this->service->save(['id' => $contact?->id ?? 0, 'customer_id' => $customer->id] + $values);
                ContactNotice::push('Se guardó el contacto ' . $saved->name . '.');
                return Response::redirect(Url::to('/admin/clientes/' . $customer->id));
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        return $this->view('admin.contacts.form', ['pageTitle' => $contact ? 'Editar contacto' : 'Nuevo contacto', 'customer' => $customer, 'contact' => $contact, 'values' => $values, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin');
    }
}
