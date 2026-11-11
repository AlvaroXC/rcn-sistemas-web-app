<?php

namespace Tests\Unit\Services\Customers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerServiceValidationTest extends TestCase
{
    public function test_it_rejects_a_missing_phone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $repository = new class implements CustomerRepository {
            public function findByPhone(string $phone): ?Customer { return null; }
            public function find(int $customerId): ?Customer { return null; }
            public function findByRfc(string $rfc): ?Customer { return null; }
            public function save(array $data): ?Customer { return null; }
        };
        (new CustomerService($repository, new CustomerIdentity()))->save(['name' => 'Cliente']);
    }
}
