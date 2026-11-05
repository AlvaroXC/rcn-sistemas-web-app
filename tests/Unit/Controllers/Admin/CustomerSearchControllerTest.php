<?php

namespace Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Controllers\Admin\CustomerSearchController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Repositories\CustomerSearchRepository;

final class CustomerSearchControllerTest extends TestCase
{
    public function test_it_returns_matches_for_a_query_of_at_least_three_characters(): void
    {
        $repository = new class implements CustomerSearchRepository {
            public string $query = '';
            public int $limit = 0;

            public function search(string $query, int $limit = 5): array
            {
                $this->query = $query;
                $this->limit = $limit;

                return [['id' => 7, 'name' => 'Ana Cliente']];
            }
        };

        $response = (new CustomerSearchController($repository))->index(new Request('GET', '/admin/clientes/buscar', ['q' => 'Ana']));
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Ana', $repository->query);
        self::assertSame(5, $repository->limit);
        self::assertSame([['id' => 7, 'name' => 'Ana Cliente']], $payload['results']);
        self::assertSame(1, $payload['meta']['returned']);
    }

    public function test_it_does_not_query_for_less_than_three_characters(): void
    {
        $repository = new class implements CustomerSearchRepository {
            public bool $wasCalled = false;

            public function search(string $query, int $limit = 5): array
            {
                $this->wasCalled = true;

                return [];
            }
        };

        $response = (new CustomerSearchController($repository))->index(new Request('GET', '/admin/clientes/buscar', ['q' => 'An']));
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($repository->wasCalled);
        self::assertSame([], $payload['results']);
        self::assertSame(3, $payload['meta']['min_length']);
    }
}
