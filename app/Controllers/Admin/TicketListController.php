<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\TicketQueryRepository;
use RcnSistemas\Services\Tickets\TicketListService;

final class TicketListController extends Controller
{
    public function __construct(private readonly TicketQueryRepository $tickets, private readonly TicketListService $filters, ViewRenderer $views) { parent::__construct($views); }

    public function index(Request $request, int $viewerId, string $viewerRole): Response
    {
        $filters = $this->filters->filters($_GET);
        $page = max(1, (int) $request->query('page', 1)); $perPage = 10;
        $total = $this->tickets->count($filters, $viewerId, $viewerRole);
        return $this->view('admin.tickets.index', ['pageTitle' => 'Tickets', 'tickets' => $this->tickets->list($filters, $viewerId, $viewerRole, $perPage, ($page - 1) * $perPage), 'filters' => $filters, 'page' => $page, 'totalPages' => max(1, (int) ceil($total / $perPage)), 'total' => $total], 'layouts.admin');
    }
}
