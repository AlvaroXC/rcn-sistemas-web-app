<?php

namespace RcnSistemas\Services;

use InvalidArgumentException;
use RcnSistemas\Models\AdminUser;
use RcnSistemas\Repositories\AdminUserManagementRepository;

final class AdminUserService
{
    public function __construct(private readonly AdminUserManagementRepository $users) {}

    public function save(array $data, int $userId = 0): AdminUser
    {
        if (trim((string) ($data['username'] ?? '')) === '') throw new InvalidArgumentException('El usuario es obligatorio.');
        if ($userId === 0 && trim((string) ($data['password'] ?? '')) === '') throw new InvalidArgumentException('La contraseña es obligatoria.');
        if (!in_array($data['role'] ?? 'agent', ['admin', 'agent'], true)) throw new InvalidArgumentException('El rol no es válido.');
        $user = $this->users->save($data, $userId);
        if (!$user) throw new InvalidArgumentException('No se pudo guardar el usuario.');
        return $user;
    }
}
