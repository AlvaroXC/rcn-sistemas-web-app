<?php

namespace RcnSistemas\Repositories;

interface AdminRememberTokenRepository
{
    public function create(int $userId, string $selector, string $tokenHash, string $expiresAt): bool;

    public function findBySelector(string $selector): ?array;

    public function rotate(int $tokenId, string $selector, string $tokenHash, string $expiresAt): bool;

    public function deleteBySelector(string $selector): bool;
}
