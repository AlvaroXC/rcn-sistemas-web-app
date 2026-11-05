<?php

namespace Tests\Unit\Services\Customers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerServiceDuplicatePhoneTest extends TestCase
{
    public function test_it_rejects_a_new_customer_with_an_existing_phone(): void
    {
        $repository = new PhoneOwnerCustomerRepository(Customer::fromArray(['id' => 7, 'name' => 'Angelica', 'phone' => '9983457421']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ya existe un cliente con ese teléfono: Angelica (#7)');

        try {
            (new CustomerService($repository, new CustomerIdentity()))->save(['name' => 'Otro', 'phone' => '998 345 7421'], true);
        } finally {
            self::assertFalse($repository->saved);
        }
    }

    public function test_it_rejects_editing_a_customer_with_a_phone_owned_by_another(): void
    {
        $repository = new PhoneOwnerCustomerRepository(Customer::fromArray(['id' => 7, 'name' => 'Angelica', 'phone' => '9983457421']));

        $this->expectException(InvalidArgumentException::class);

        (new CustomerService($repository, new CustomerIdentity()))->save(['id' => 9, 'name' => 'Otro', 'phone' => '9983457421'], true);
    }

    public function test_it_allows_saving_the_same_customer_with_its_own_phone(): void
    {
        $repository = new PhoneOwnerCustomerRepository(Customer::fromArray(['id' => 7, 'name' => 'Angelica', 'phone' => '9983457421']));

        $customer = (new CustomerService($repository, new CustomerIdentity()))->save(['id' => 7, 'name' => 'Angelica', 'phone' => '9983457421'], true);

        self::assertSame(7, $customer->id);
        self::assertTrue($repository->saved);
    }

    public function test_it_keeps_updating_the_existing_customer_when_rejection_is_off(): void
    {
        $repository = new PhoneOwnerCustomerRepository(Customer::fromArray(['id' => 7, 'name' => 'Angelica', 'phone' => '9983457421']));

        (new CustomerService($repository, new CustomerIdentity()))->save(['name' => 'Angelica', 'phone' => '9983457421']);

        self::assertTrue($repository->saved);
    }
}

final class PhoneOwnerCustomerRepository implements CustomerRepository
{
    public bool $saved = false;

    public function __construct(private readonly Customer $owner) {}

    public function findByPhone(string $phone): ?Customer { return $phone === $this->owner->phone ? $this->owner : null; }
    public function find(int $customerId): ?Customer { return null; }
    public function findByRfc(string $rfc): ?Customer { return null; }
    public function save(array $data): ?Customer
    {
        $this->saved = true;
        return Customer::fromArray(['id' => (int) ($data['id'] ?? 0) ?: 1] + $data);
    }
}
