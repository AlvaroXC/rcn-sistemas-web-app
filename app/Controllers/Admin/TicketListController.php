<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\TicketQueryRepository;
use RcnSistemas\Services\Tickets\TicketListService;
use RcnSistemas\Services\Tickets\TicketCatalog;
use RcnSistemas\Services\Tickets\TicketWorkflow;

final class TicketListController extends Controller
{
    public function __construct(private readonly TicketQueryRepository $tickets, private readonly TicketListService $filters, ViewRenderer $views) { parent::__construct($views); }

    public function index(Request $request, int $viewerId, string $viewerRole): Response
    {
        $filters = $this->filters->filters([
            'scope' => $request->query('scope', 'open'),
            'customer' => $request->query('customer', ''),
            'status' => $request->query('status', ''),
            'consultation_type' => $request->query('consultation_type', ''),
            'assigned_user_id' => $request->query('assigned_user_id', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
        ]);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) $perPage = 10;
        $filters['per_page'] = $perPage;
        $total = $this->tickets->count($filters, $viewerId, $viewerRole);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $isOpenScope = $filters['scope'] === 'open';
        $activeFilters = [];
        foreach (['customer' => 'Cliente', 'status' => 'Estado', 'consultation_type' => 'Tipo', 'date_from' => 'Desde', 'date_to' => 'Hasta'] as $key => $label) {
            if ($filters[$key] !== '') $activeFilters[] = $label . ': ' . $filters[$key];
        }
        if ($filters['assigned_user_id'] !== '') $activeFilters[] = 'Responsable: ' . ($filters['assigned_user_id'] === 'unassigned' ? 'Sin responsable' : $filters['assigned_user_id']);
        $stats = $this->tickets->stats($viewerId, $viewerRole);
        $scopeTitle = $isOpenScope ? 'Tickets abiertos' : 'Todos los tickets';
        $scopeDescription = $isOpenScope ? 'Vista operativa de tickets pendientes.' : 'Vista histórica de todos los tickets.';
        return $this->view('admin.tickets.index', [
            'pageTitle' => 'Tickets',
            'tickets' => $this->tickets->list($filters, $viewerId, $viewerRole, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'isOpenScope' => $isOpenScope,
            'scopeTitle' => $scopeTitle,
            'scopeDescription' => $scopeDescription,
            'activeFilters' => $activeFilters,
            'statuses' => (new TicketWorkflow())->statuses(),
            'types' => TicketCatalog::TYPES,
            'ticketStats' => $stats,
            'responsibleUsers' => $this->tickets->responsibleUsers(),
        ], 'layouts.admin');
    }
}
