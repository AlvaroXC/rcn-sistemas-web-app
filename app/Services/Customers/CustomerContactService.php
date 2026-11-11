<?php

namespace RcnSistemas\Services\Customers;

use InvalidArgumentException;
use RcnSistemas\Models\CustomerContact;
use RcnSistemas\Repositories\CustomerContactRepository;
use RcnSistemas\Repositories\CustomerRepository;

/** Contactos de un cliente: personas que hablan en su nombre. Teléfono y correo pueden repetirse. */
final class CustomerContactService
{
    public function __construct(
        private readonly CustomerContactRepository $contacts,
        private readonly CustomerRepository $customers,
        private readonly CustomerIdentity $identity = new CustomerIdentity()
    ) {
    }

    public function save(array $data): CustomerContact
    {
        $contactId = (int) ($data['id'] ?? 0);
        $current = $contactId > 0 ? $this->contacts->find($contactId) : null;
        if ($contactId > 0 && !$current) {
            throw new InvalidArgumentException('El contacto no existe.');
        }
        // Un contacto siempre se queda con el cliente para el que se creó.
        $customerId = $current ? $current->customerId : (int) ($data['customer_id'] ?? 0);
        if (!$this->customers->find($customerId)) {
            throw new InvalidArgumentException('El cliente del contacto no existe.');
        }

        $name = trim(preg_replace('/\s+/u', ' ', (string) ($data['name'] ?? '')) ?? '');
        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Indica el nombre del contacto (máximo 150 caracteres).');
        }
        $phone = $this->identity->normalizePhone((string) ($data['phone'] ?? ''));
        if (mb_strlen($phone) > 50) {
            throw new InvalidArgumentException('El teléfono supera el máximo de 50 caracteres.');
        }
        $email = $this->identity->normalizeEmail($data['email'] ?? null);
        if ($email !== null && mb_strlen($email) > 255) {
            throw new InvalidArgumentException('El correo supera el máximo de 255 caracteres.');
        }

        $contact = $this->contacts->save(['id' => $contactId, 'customer_id' => $customerId, 'name' => $name, 'phone' => $phone, 'email' => $email]);
        if (!$contact) {
            throw new InvalidArgumentException('No se pudo guardar el contacto.');
        }

        return $contact;
    }
}
