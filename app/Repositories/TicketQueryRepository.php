<?php

namespace RcnSistemas\Repositories;

interface TicketQueryRepository
{
    public function count(array $filters, int $viewerId, string $viewerRole): int;

    public function list(array $filters, int $viewerId, string $viewerRole, int $limit, int $offset): array;

    /** @return array{total:int,open:int,customers:int} */
    public function stats(int $viewerId, string $viewerRole): array;

    /** @return list<array{id:int,username:string,display_name:?string}> */
    public function responsibleUsers(): array;
}
