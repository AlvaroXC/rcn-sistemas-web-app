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

    /**
     * @param bool $rejectDuplicatePhone Si es true, lanza error cuando el teléfono ya pertenece a otro cliente
     *                                   (uso en el panel admin). Si es false, se actualiza el cliente existente
     *                                   (uso en el formulario público de contacto).
     */
    public function save(array $data, bool $rejectDuplicatePhone = false): Customer
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio.');
        }

        $data['phone'] = $this->identity->normalizePhone((string) ($data['phone'] ?? ''));
        $data['email'] = $this->identity->normalizeEmail($data['email'] ?? null);
        if ($rejectDuplicatePhone) {
            $phoneOwner = $this->customers->findByPhone($data['phone']);
            if ($phoneOwner && $phoneOwner->id !== (int) ($data['id'] ?? 0)) {
                throw new InvalidArgumentException('Ya existe un cliente con ese teléfono: ' . $phoneOwner->name . ' (#' . $phoneOwner->id . '). Búscalo y selecciónalo en lugar de crear uno nuevo.');
            }
        }
        if (array_key_exists('rfc', $data)) {
            $data['rfc'] = $this->identity->normalizeRfc($data['rfc']);
            $targetId = (int) ($data['id'] ?? 0) ?: ($this->customers->findByPhone($data['phone'])?->id ?? 0);
            $owner = $data['rfc'] !== null ? $this->customers->findByRfc($data['rfc']) : null;
            if ($owner && $owner->id !== $targetId) {
                throw new InvalidArgumentException('Ya existe un cliente registrado con ese RFC.');
            }
        }
        $customer = $this->customers->save($data);

        if (!$customer) {
            throw new InvalidArgumentException('No se pudo guardar el cliente.');
        }

        return $customer;
    }
}
