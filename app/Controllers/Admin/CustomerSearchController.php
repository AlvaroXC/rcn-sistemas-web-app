<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Repositories\CustomerSearchRepository;

final class CustomerSearchController
{
    public function __construct(private readonly CustomerSearchRepository $customers) {}

    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $results = mb_strlen($query) >= 3 ? $this->customers->search($query, 5) : [];
        return Response::json(['ok' => true, 'query' => $query, 'results' => $results, 'meta' => ['min_length' => 3, 'returned' => count($results)]]);
    }
}
