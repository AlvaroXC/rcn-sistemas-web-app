<?php

namespace Tests\Unit\Services\Google;

use Google\Service\Calendar\Event;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Models\GoogleCalendarIntegration;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\GoogleCalendarEventMigrationRepository;
use RcnSistemas\Repositories\GoogleCalendarIntegrationRepository;
use RcnSistemas\Repositories\TicketCalendarRepository;
use RcnSistemas\Repositories\TicketRepository;
use RcnSistemas\Services\Google\GoogleCalendarAppointmentSyncService;
use RcnSistemas\Services\Google\GoogleCalendarEventMigrationService;
use RcnSistemas\Services\Google\GoogleCalendarGateway;

final class GoogleCalendarEventMigrationServiceTest extends TestCase
{
    public function test_migration_can_resume_after_a_temporary_event_creation_failure(): void
    {
        $tickets = new MigrationFakeTicketRepository(self::ticketData());
        $ticketCalendar = new MigrationFakeTicketCalendarRepository($tickets);
        $migrationRepository = new MigrationFakeRepository($tickets);
        $integrations = new MigrationFakeIntegrationRepository();
        $gateway = new MigrationFakeGateway();
        $gateway->createFailure = new \RuntimeException('temporary error');
        $sync = new GoogleCalendarAppointmentSyncService($integrations, $ticketCalendar, $gateway, ['timezone' => 'America/Merida']);
        $service = new GoogleCalendarEventMigrationService($migrationRepository, $tickets, $integrations, new MigrationFakeLegacyConnections(), $sync, $gateway);

        $firstRun = $service->run(10);
        self::assertSame(1, $firstRun['failed']);
        self::assertSame(1, $service->report()['pending']);

        $gateway->createFailure = null;
        $secondRun = $service->run(10);

        self::assertSame(1, $secondRun['migrated']);
        self::assertSame(0, $service->report()['pending']);
        self::assertSame('org-calendar', $tickets->row['google_calendar_target_id']);
    }

    public function test_failed_legacy_cleanup_is_reported_and_can_be_retried(): void
    {
        $row = self::ticketData([
            'google_calendar_event_id' => 'old-event',
            'google_calendar_legacy_event_id' => 'old-event',
            'google_calendar_legacy_calendar_id' => 'old-calendar',
            'google_calendar_legacy_cleanup_status' => 'pending',
        ]);
        $tickets = new MigrationFakeTicketRepository($row);
        $ticketCalendar = new MigrationFakeTicketCalendarRepository($tickets);
        $migrationRepository = new MigrationFakeRepository($tickets);
        $integrations = new MigrationFakeIntegrationRepository();
        $gateway = new MigrationFakeGateway();
        $gateway->deleteFailure = new \RuntimeException('cleanup error');
        $sync = new GoogleCalendarAppointmentSyncService($integrations, $ticketCalendar, $gateway, ['timezone' => 'America/Merida']);
        $service = new GoogleCalendarEventMigrationService($migrationRepository, $tickets, $integrations, new MigrationFakeLegacyConnections(), $sync, $gateway);

        $service->run(10);

        self::assertSame('synced', $tickets->row['google_calendar_sync_status']);
        self::assertSame(1, $service->report()['cleanup_failed']);
        self::assertSame([41], $service->report()['cleanup_failed_ticket_ids']);

        $gateway->deleteFailure = null;
        $service->run(10);

        self::assertSame('cleaned', $tickets->row['google_calendar_legacy_cleanup_status']);
        self::assertSame(0, $service->report()['cleanup_failed']);
    }

    private static function ticketData(array $overrides = []): array
    {
        return array_replace([
            'id' => 41,
            'customer_id' => 4,
            'consultation_type' => 'Soporte',
            'status' => 'Nuevo',
            'origin_type' => 'admin',
            'message' => 'Cita histórica',
            'assigned_user_id' => 9,
            'customer_name' => 'Cliente',
            'appointment_date' => '2026-10-02',
            'appointment_time' => '11:00:00',
            'google_calendar_sync_status' => 'pending',
            'google_calendar_migration_status' => 'pending',
            'google_calendar_legacy_cleanup_status' => 'not_required',
        ], $overrides);
    }
}

final class MigrationFakeTicketRepository implements TicketRepository
{
    public function __construct(public array $row) {}
    public function create(array $data): ?Ticket { return null; }
    public function find(int $ticketId): ?Ticket { return $ticketId === (int) $this->row['id'] ? Ticket::fromArray($this->row) : null; }
    public function updateStatus(int $ticketId, string $status): bool { return true; }
    public function updatePrice(int $ticketId, array $price, int $userId): bool { return true; }
    public function updateSchedule(int $ticketId, ?string $date, ?string $time, ?string $serviceDate, ?string $serviceStart, ?string $serviceEnd): bool { return true; }
    public function updateAssignee(int $ticketId, int $userId, int $actorId): bool { return true; }
}

final class MigrationFakeTicketCalendarRepository implements TicketCalendarRepository
{
    public function __construct(private readonly MigrationFakeTicketRepository $tickets) {}

    public function updateCalendarSync(int $ticketId, ?string $eventId, ?string $eventLink, string $status, ?string $error = null, ?string $targetCalendarId = null, ?string $migrationStatus = null, ?string $legacyCleanupStatus = null, ?string $legacyCleanupError = null, ?string $legacyEventId = null, ?string $legacyCalendarId = null, ?int $legacyUserId = null): bool
    {
        $this->tickets->row = array_replace($this->tickets->row, [
            'google_calendar_event_id' => $eventId,
            'google_calendar_event_link' => $eventLink,
            'google_calendar_sync_status' => $status,
            'google_calendar_sync_error' => $error,
            'google_calendar_target_id' => $targetCalendarId,
            'google_calendar_migration_status' => $migrationStatus,
            'google_calendar_legacy_cleanup_status' => $legacyCleanupStatus,
            'google_calendar_legacy_cleanup_error' => $legacyCleanupError,
            'google_calendar_legacy_event_id' => $legacyEventId,
            'google_calendar_legacy_calendar_id' => $legacyCalendarId,
            'google_calendar_legacy_user_id' => $legacyUserId,
        ]);
        return true;
    }
}

final class MigrationFakeRepository implements GoogleCalendarEventMigrationRepository
{
    public function __construct(private readonly MigrationFakeTicketRepository $tickets) {}
    public function candidateIds(int $limit): array
    {
        $row = $this->tickets->row;
        return $row['google_calendar_migration_status'] === 'pending' || in_array($row['google_calendar_legacy_cleanup_status'], ['pending', 'failed'], true) ? [(int) $row['id']] : [];
    }
    public function summary(): array
    {
        $row = $this->tickets->row;
        return [
            'total' => 1,
            'pending' => $row['google_calendar_migration_status'] === 'pending' ? 1 : 0,
            'migrated' => $row['google_calendar_migration_status'] === 'migrated' ? 1 : 0,
            'cleanup_pending' => $row['google_calendar_legacy_cleanup_status'] === 'pending' ? 1 : 0,
            'cleanup_failed' => $row['google_calendar_legacy_cleanup_status'] === 'failed' ? 1 : 0,
            'cleanup_failed_ticket_ids' => $row['google_calendar_legacy_cleanup_status'] === 'failed' ? [(int) $row['id']] : [],
        ];
    }
    public function updateLegacyCleanup(int $ticketId, string $status, ?string $error, bool $clearLegacyReference): bool
    {
        $this->tickets->row['google_calendar_legacy_cleanup_status'] = $status;
        $this->tickets->row['google_calendar_legacy_cleanup_error'] = $error;
        if ($clearLegacyReference) {
            $this->tickets->row['google_calendar_legacy_event_id'] = null;
            $this->tickets->row['google_calendar_legacy_calendar_id'] = null;
            $this->tickets->row['google_calendar_legacy_user_id'] = null;
        }
        return true;
    }
    public function markNotApplicable(int $ticketId): bool { $this->tickets->row['google_calendar_migration_status'] = 'not_applicable'; return true; }
}

final class MigrationFakeIntegrationRepository implements GoogleCalendarIntegrationRepository
{
    private GoogleCalendarIntegration $integration;
    public function __construct() { $this->integration = new GoogleCalendarIntegration('id', 'shared@example.test', 'access', 'refresh', null, 'org-calendar', 'calendar.events', 'connected', null, null, null); }
    public function findShared(): ?GoogleCalendarIntegration { return $this->integration; }
    public function saveShared(array $data): ?GoogleCalendarIntegration { return $this->integration; }
    public function markSharedError(string $status, string $error): bool { return true; }
    public function disconnectShared(): bool { return true; }
}

final class MigrationFakeLegacyConnections implements GoogleCalendarConnectionRepository
{
    public function findByUserId(int $userId): ?GoogleCalendarConnection { return null; }
    public function save(array $data): ?GoogleCalendarConnection { return null; }
    public function markError(int $userId, string $status, string $error): bool { return true; }
    public function disconnect(int $userId): bool { return true; }
}

final class MigrationFakeGateway implements GoogleCalendarGateway
{
    public ?\Throwable $createFailure = null;
    public ?\Throwable $deleteFailure = null;
    public function authorizationUrl(string $state): string { return ''; }
    public function exchangeCode(string $code): array { return []; }
    public function createEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, array $data, ?string $eventId = null): Event
    {
        if ($this->createFailure) throw $this->createFailure;
        return new Event(['id' => $eventId, 'htmlLink' => 'https://calendar.test/events/' . $eventId]);
    }
    public function updateEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId, array $data): Event
    {
        return new Event(['id' => $eventId, 'htmlLink' => 'https://calendar.test/events/' . $eventId]);
    }
    public function deleteEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId): void
    {
        if ($this->deleteFailure) throw $this->deleteFailure;
    }
    public function revoke(GoogleCalendarConnection|GoogleCalendarIntegration $connection): void {}
}
