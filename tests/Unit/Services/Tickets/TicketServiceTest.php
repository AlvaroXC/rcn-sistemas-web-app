<?php

namespace Tests\Unit\Services\Tickets;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\TicketRepository;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;

final class TicketServiceTest extends TestCase
{
    public function test_it_creates_a_new_ticket(): void
    {
        $service = new TicketService(new InMemoryTicketRepository(), new TicketWorkflow());

        $ticket = $service->create(['customer_id' => 4, 'message' => 'Necesito ayuda']);

        self::assertSame(4, $ticket->customerId);
        self::assertSame('Nuevo', $ticket->status);
    }

    public function test_it_does_not_change_a_closed_ticket(): void
    {
        $service = new TicketService(new InMemoryTicketRepository(), new TicketWorkflow());

        $this->expectException(InvalidArgumentException::class);
        $service->changeStatus(Ticket::fromArray(['id' => 1, 'status' => 'Cerrado']), 'Resuelto');
    }
}

final class InMemoryTicketRepository implements TicketRepository
{
    public function create(array $data): ?Ticket { return Ticket::fromArray(['id' => 1] + $data); }
    public function find(int $ticketId): ?Ticket { return null; }
    public function updateStatus(int $ticketId, string $status): bool { return true; }
    public function updatePrice(int $ticketId, array $price, int $userId): bool { return true; }
    public function updateSchedule(int $ticketId, ?string $date, ?string $time, ?string $serviceDate, ?string $serviceStart, ?string $serviceEnd): bool { return true; }
    public function updateAssignee(int $ticketId, int $userId, int $actorId): bool { return true; }
}
