<?php

namespace RcnSistemas\Services\Customers;

use InvalidArgumentException;
use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\CustomerRepository;

final class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerIdentity $identity
    ) {
    }

    public function save(array $data): Customer
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio.');
        }

        $data['phone'] = $this->identity->normalizePhone((string) ($data['phone'] ?? ''));
        $data['email'] = $this->identity->normalizeEmail($data['email'] ?? null);
        $customer = $this->customers->save($data);

        if (!$customer) {
            throw new InvalidArgumentException('No se pudo guardar el cliente.');
        }

        return $customer;
    }
}
