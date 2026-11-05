<?php

namespace Tests\Unit\Services\Customers;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerServiceTest extends TestCase
{
    public function test_it_normalizes_identity_before_saving(): void
    {
        $repository = new InMemoryCustomerRepository();
        $customer = (new CustomerService($repository, new CustomerIdentity()))->save([
            'name' => 'Cliente', 'phone' => '+52 (999) 994-2427', 'email' => 'CLIENTE@EXAMPLE.COM',
        ]);

        self::assertSame('+529999942427', $repository->data['phone']);
        self::assertSame('cliente@example.com', $repository->data['email']);
        self::assertSame('Cliente', $customer->name);
    }
}

final class InMemoryCustomerRepository implements CustomerRepository
{
    public array $data = [];

    public function findByPhone(string $phone): ?Customer { return null; }
    public function find(int $customerId): ?Customer { return null; }
    public function findByRfc(string $rfc): ?Customer { return null; }
    public function save(array $data): ?Customer
    {
        $this->data = $data;
        return Customer::fromArray(['id' => 1] + $data);
    }
}
