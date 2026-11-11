<?php

namespace Tests\Unit\Services\Customers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;

final class CustomerRfcTest extends TestCase
{
    public function test_it_normalizes_rfc_and_derives_the_person_type(): void
    {
        $identity = new CustomerIdentity();

        $this->assertSame('AMO050128JC3', $identity->normalizeRfc(' amo-050128 jc3 '));
        $this->assertSame('MUÑA800101AB1', $identity->normalizeRfc('muña800101ab1'));
        $this->assertNull($identity->normalizeRfc(''));
        $this->assertSame('persona_moral', $identity->customerTypeForRfc('AMO050128JC3'));
        $this->assertSame('persona_fisica', $identity->customerTypeForRfc('CILL8211177M0'));
    }

    public function test_it_rejects_an_invalid_rfc(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CustomerIdentity())->normalizeRfc('ABC123');
    }

    public function test_it_rejects_an_rfc_owned_by_another_customer_but_allows_the_same_one(): void
    {
        $repository = $this->repository(new Customer(5, 'Otro', null, '111', '', 'persona_moral', 'AMO050128JC3'));
        $service = new CustomerService($repository, new CustomerIdentity());

        $service->save(['id' => 5, 'name' => 'Otro', 'phone' => '111', 'rfc' => 'amo050128jc3']);
        $this->assertSame('AMO050128JC3', $repository->saved['rfc']);

        $this->expectException(InvalidArgumentException::class);
        $service->save(['id' => 0, 'name' => 'Nuevo', 'phone' => '222', 'rfc' => 'AMO050128JC3']);
    }

    public function test_saving_without_rfc_key_keeps_it_untouched(): void
    {
        $repository = $this->repository(null);
        (new CustomerService($repository, new CustomerIdentity()))->save(['name' => 'Contacto', 'phone' => '333']);

        $this->assertArrayNotHasKey('rfc', $repository->saved);
    }

    private function repository(?Customer $owner): CustomerRepository
    {
        return new class($owner) implements CustomerRepository {
            public array $saved = [];
            public function __construct(private readonly ?Customer $owner) {}
            public function findByPhone(string $phone): ?Customer { return $this->owner && $this->owner->phone === $phone ? $this->owner : null; }
            public function find(int $customerId): ?Customer { return null; }
            public function findByRfc(string $rfc): ?Customer { return $this->owner?->rfc === $rfc ? $this->owner : null; }
            public function save(array $data): ?Customer { $this->saved = $data; return Customer::fromArray(['id' => (int) ($data['id'] ?? 0) ?: 9] + $data); }
        };
    }
}
