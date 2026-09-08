<?php

namespace Tests\Integration;

use mysqli;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliTicketQueryRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Tickets\TicketListService;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;

final class MigrationFlowTest extends TestCase
{
    private mysqli $connection;

    protected function setUp(): void
    {
        if (getenv('RUN_DB_TESTS') !== '1') self::markTestSkipped('Define RUN_DB_TESTS=1 para ejecutar pruebas de integración.');
        $this->connection = new mysqli((string) getenv('DB_HOST') ?: '127.0.0.1', (string) getenv('DB_USER') ?: 'root', (string) getenv('DB_PASS'), (string) getenv('DB_NAME') ?: 'rcnsistemas_testing', (int) (getenv('DB_PORT') ?: 3306));
        if ($this->connection->connect_error) self::fail($this->connection->connect_error);
        $this->connection->begin_transaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) $this->connection->rollback();
    }

    public function test_it_creates_a_customer_and_ticket(): void
    {
        $customers = new CustomerService(new MysqliCustomerRepository($this->connection), new CustomerIdentity());
        $customer = $customers->save(['name' => 'Integración', 'phone' => '+529991' . random_int(100000, 999999)]);
        $ticket = (new TicketService(new MysqliTicketRepository($this->connection), new TicketWorkflow()))->create(['customer_id' => $customer->id, 'message' => 'Prueba de migración', 'assigned_user_id' => 999]);
        self::assertSame($customer->id, $ticket->customerId);
    }

    public function test_it_limits_tickets_to_the_assigned_operator(): void
    {
        $customers = new CustomerService(new MysqliCustomerRepository($this->connection), new CustomerIdentity());
        $customer = $customers->save(['name' => 'Asignado', 'phone' => '+529992' . random_int(100000, 999999)]);
        (new TicketService(new MysqliTicketRepository($this->connection), new TicketWorkflow()))->create(['customer_id' => $customer->id, 'message' => 'Acceso', 'assigned_user_id' => 999]);
        $query = new MysqliTicketQueryRepository($this->connection); $filters = (new TicketListService())->filters(['scope' => 'all']);
        self::assertSame(1, $query->count($filters, 999, 'agent'));
        self::assertSame(0, $query->count($filters, 998, 'agent'));
    }
}
