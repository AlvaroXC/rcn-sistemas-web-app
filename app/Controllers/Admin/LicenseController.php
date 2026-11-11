<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Models\Customer;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Repositories\LicenseQuotationRepository;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Services\Licenses\CustomerLicenseService;
use RcnSistemas\Services\Licenses\LicenseCatalog;
use RcnSistemas\Services\Licenses\LicenseListFilter;
use RcnSistemas\Services\Licenses\LicenseNotice;

final class LicenseController extends Controller
{
    private CustomerLicenseService $service;

    public function __construct(
        private readonly CustomerLicenseRepository $licenses,
        private readonly CustomerRepository $customers,
        private readonly ProductRepository $products,
        private readonly LicenseQuotationRepository $quotations,
        ViewRenderer $views
    ) {
        parent::__construct($views);
        $this->service = new CustomerLicenseService($licenses, $customers);
    }

    public function index(Request $request): Response
    {
        $today = date('Y-m-d');
        $filter = LicenseListFilter::fromQuery(['search' => $request->query('search', ''), 'status' => $request->query('status', ''), 'month' => $request->query('month', ''), 'year' => $request->query('year', '')], $today);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [10, 25, 50], true)) $perPage = 25;
        $years = $this->licenses->expirationYears();
        $years = array_values(array_unique([...$years, (int) date('Y')]));
        sort($years);
        $total = $this->licenses->count($filter);
        return $this->view('admin.licenses.index', ['pageTitle' => 'Licencias', 'licenses' => $this->licenses->list($filter, $perPage, ($page - 1) * $perPage), 'filter' => $filter, 'years' => $years, 'page' => $page, 'perPage' => $perPage, 'total' => $total, 'totalPages' => max(1, (int) ceil($total / $perPage)), 'today' => $today, 'licenseNotice' => LicenseNotice::pull(), 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin');
    }

    public function create(Request $request): Response
    {
        $customer = $this->customers->find((int) $request->query('id', 0));
        if (!$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $customer, null);
    }

    public function edit(Request $request): Response
    {
        $license = $this->licenses->find((int) $request->query('id', 0));
        $customer = $license ? $this->customers->find($license->customerId) : null;
        if (!$license || !$customer) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $customer, $license);
    }

    public function delete(Request $request): Response
    {
        $license = $this->licenses->find((int) $request->query('id', 0));
        if (!$license) return $this->view('errors.404', [], 'layouts.admin', 404);
        $this->licenses->delete($license->id);
        return Response::redirect(Url::to('/admin/clientes/' . $license->customerId));
    }

    public function renew(Request $request): Response
    {
        $returnTo = (string) $request->input('return_to', '');
        // Solo rutas internas del panel, para no redirigir a otro sitio.
        if (!preg_match('#^/admin/[A-Za-z0-9/_-]*(\?\S*)?$#', $returnTo) || str_contains($returnTo, '//') || str_contains($returnTo, '\\')) {
            $returnTo = '/admin/licencias';
        }
        try {
            $license = $this->service->renew((int) $request->query('id', 0), (string) $request->input('expected_expires_at', ''), date('Y-m-d'));
            LicenseNotice::push('success', 'Licencia ' . ($license->alias ? $license->alias . ' (' . $license->productName . ')' : $license->productName) . ' renovada: ahora vence el ' . date('d/m/Y', strtotime((string) $license->expiresAt)) . '.');
        } catch (InvalidArgumentException $exception) {
            LicenseNotice::push('error', $exception->getMessage());
        }

        return Response::redirect(Url::to($returnTo));
    }

    private function form(Request $request, Customer $customer, ?CustomerLicense $license): Response
    {
        $error = null;
        $values = $license ? ['product_name' => $license->productName, 'alias' => $license->alias, 'license_type' => $license->licenseType, 'rfc_scope' => $license->rfcScope, 'users_count' => $license->usersCount, 'serial_number' => $license->serialNumber, 'version' => $license->version, 'expires_at' => $license->expiresAt, 'notes' => $license->notes] : ['product_name' => '', 'alias' => '', 'license_type' => 'anual', 'rfc_scope' => 'multi', 'users_count' => 1, 'serial_number' => '', 'version' => '', 'expires_at' => '', 'notes' => ''];
        if ($request->isMethod('POST')) {
            foreach (array_keys($values) as $field) $values[$field] = $request->input($field, '');
            try {
                $this->service->save(['id' => $license?->id ?? 0, 'customer_id' => $customer->id] + $values);
                return Response::redirect(Url::to('/admin/clientes/' . $customer->id));
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        return $this->view('admin.licenses.form', ['pageTitle' => $license ? 'Editar licencia' : 'Nueva licencia', 'customer' => $customer, 'license' => $license, 'values' => $values, 'error' => $error, 'today' => date('Y-m-d'), 'licenseNotice' => $request->isMethod('POST') ? null : LicenseNotice::pull(), 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'quotations' => $license ? $this->quotations->forLicense($license->id) : [], 'productNames' => $this->productNames()], 'layouts.admin');
    }

    /** @return list<string> nombres de los productos registrados, en orden alfabético y sin repetir */
    private function productNames(): array
    {
        $names = array_values(array_unique(array_map(static fn ($product): string => trim((string) $product->name), $this->products->all())));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }
}
