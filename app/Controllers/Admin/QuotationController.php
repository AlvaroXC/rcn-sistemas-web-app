<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Models\LicenseQuotation;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Repositories\LicenseQuotationRepository;
use RcnSistemas\Services\Licenses\LicenseNotice;
use RcnSistemas\Services\Licenses\LicenseQuotationService;

final class QuotationController extends Controller
{
    private LicenseQuotationService $service;

    public function __construct(
        private readonly LicenseQuotationRepository $quotations,
        private readonly CustomerLicenseRepository $licenses,
        private readonly CustomerRepository $customers,
        ViewRenderer $views
    ) {
        parent::__construct($views);
        $this->service = new LicenseQuotationService($quotations, $licenses);
    }

    public function create(Request $request): Response
    {
        $license = $this->licenses->find((int) $request->query('id', 0));
        if (!$license) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $license, null);
    }

    public function edit(Request $request): Response
    {
        $quotation = $this->quotations->find((int) $request->query('id', 0));
        $license = $quotation ? $this->licenses->find($quotation->licenseId) : null;
        if (!$quotation || !$license) return $this->view('errors.404', [], 'layouts.admin', 404);
        return $this->form($request, $license, $quotation);
    }

    public function delete(Request $request): Response
    {
        $quotation = $this->quotations->find((int) $request->query('id', 0));
        if (!$quotation) return $this->view('errors.404', [], 'layouts.admin', 404);
        $this->quotations->delete($quotation->id);
        LicenseNotice::push('success', 'Se eliminó la cotización ' . $quotation->folio . '.');
        return Response::redirect(Url::to('/admin/licencias/' . $quotation->licenseId . '/editar'));
    }

    private function form(Request $request, CustomerLicense $license, ?LicenseQuotation $quotation): Response
    {
        $error = null;
        $values = $quotation
            ? ['folio' => $quotation->folio, 'quoted_at' => $quotation->quotedAt, 'status' => $quotation->status, 'notes' => $quotation->notes]
            : ['folio' => '', 'quoted_at' => date('Y-m-d'), 'status' => 'enviada', 'notes' => ''];
        if ($request->isMethod('POST')) {
            foreach (array_keys($values) as $field) $values[$field] = (string) $request->input($field, $field === 'status' ? $values['status'] : '');
            try {
                $saved = $this->service->save(['id' => $quotation?->id ?? 0, 'license_id' => $license->id] + $values);
                LicenseNotice::push('success', 'Se guardó la cotización ' . $saved->folio . '.');
                return Response::redirect(Url::to('/admin/licencias/' . $license->id . '/editar'));
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        return $this->view('admin.quotations.form', ['pageTitle' => $quotation ? 'Editar cotización' : 'Nueva cotización', 'license' => $license, 'customer' => $this->customers->find($license->customerId), 'quotation' => $quotation, 'values' => $values, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin');
    }
}
