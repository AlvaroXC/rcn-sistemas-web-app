<?php

namespace Tests\Unit\Services\Customers;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Models\CustomerContact;
use RcnSistemas\Repositories\CustomerContactRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Customers\CustomerContactService;

final class CustomerContactServiceTest extends TestCase
{
    private array $data = ['customer_id' => 7, 'name' => '  Leticia   Flores ', 'phone' => '999 123-4567', 'email' => ' Leticia@ABO.mx '];

    public function test_it_saves_a_contact_with_normalized_phone_and_email(): void
    {
        $contact = $this->service($this->contacts())->save($this->data);

        $this->assertSame([7, 'Leticia Flores', '9991234567', 'leticia@abo.mx'], [$contact->customerId, $contact->name, $contact->phone, $contact->email]);
    }

    public function test_email_is_optional(): void
    {
        $contact = $this->service($this->contacts())->save(['email' => ''] + $this->data);

        $this->assertNull($contact->email);
    }

    public function test_the_phone_may_repeat_the_customer_phone(): void
    {
        $contact = $this->service($this->contacts())->save(['phone' => '9999999999'] + $this->data);

        $this->assertSame('9999999999', $contact->phone);
    }

    public function test_editing_keeps_the_original_customer(): void
    {
        $repository = $this->contacts(new CustomerContact(3, 7, 'Leticia Flores', '9991234567', null));
        $contact = $this->service($repository)->save(['id' => 3, 'customer_id' => 8, 'phone' => '9997654321'] + $this->data);

        $this->assertSame([3, 7, '9997654321'], [$contact->id, $contact->customerId, $contact->phone]);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidData(): array
    {
        return [
            'sin nombre' => [['name' => '   '], 'nombre'],
            'nombre largo' => [['name' => str_repeat('a', 151)], 'nombre'],
            'sin telefono' => [['phone' => ''], 'teléfono'],
            'telefono sin digitos' => [['phone' => 'sin número'], 'teléfono'],
            'correo invalido' => [['email' => 'leticia@'], 'correo'],
            'cliente inexistente' => [['customer_id' => 99], 'cliente'],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_it_rejects_invalid_data(array $override, string $message): void
    {
        $repository = $this->contacts();
        try {
            $this->service($repository)->save($override + $this->data);
            $this->fail('Se esperaba que el contacto fuera rechazado.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsStringIgnoringCase($message, $exception->getMessage());
            $this->assertSame([], $repository->saved);
        }
    }

    public function test_it_rejects_editing_an_unknown_contact(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->contacts())->save(['id' => 40] + $this->data);
    }

    private function service(CustomerContactRepository $contacts): CustomerContactService
    {
        $customers = new class implements CustomerRepository {
            public function findByPhone(string $phone): ?Customer { return null; }
            public function find(int $customerId): ?Customer { return in_array($customerId, [7, 8], true) ? new Customer($customerId, 'Cliente', null, '9999999999', '', 'persona_fisica') : null; }
            public function findByRfc(string $rfc): ?Customer { return null; }
            public function save(array $data): ?Customer { throw new \LogicException('Guardar un contacto no debe modificar al cliente.'); }
        };

        return new CustomerContactService($contacts, $customers);
    }

    private function contacts(?CustomerContact $existing = null): CustomerContactRepository
    {
        return new class($existing) implements CustomerContactRepository {
            public array $saved = [];
            public function __construct(private readonly ?CustomerContact $existing) {}
            public function find(int $contactId): ?CustomerContact { return $this->existing?->id === $contactId ? $this->existing : null; }
            public function forCustomer(int $customerId): array { return []; }
            public function save(array $data): ?CustomerContact { $this->saved = $data; return CustomerContact::fromArray($data + ['id' => 1]); }
            public function delete(int $contactId): bool { return true; }
        };
    }
}
