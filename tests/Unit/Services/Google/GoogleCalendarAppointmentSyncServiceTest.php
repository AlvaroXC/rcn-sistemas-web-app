<?php

namespace Tests\Unit\Services\Google;

use Google\Service\Calendar\Event;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Controllers\Admin\GoogleCalendarController;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Models\GoogleCalendarIntegration;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\GoogleCalendarIntegrationRepository;
use RcnSistemas\Repositories\TicketCalendarRepository;
use RcnSistemas\Services\Google\GoogleCalendarAppointmentSyncService;
use RcnSistemas\Services\Google\GoogleCalendarAuthorizationException;
use RcnSistemas\Services\Google\GoogleCalendarGateway;

final class GoogleCalendarAppointmentSyncServiceTest extends TestCase
{
    private InMemorySharedCalendarRepository $integrations;
    private RecordingCalendarRepository $ticketCalendar;
    private RecordingGoogleCalendarGateway $gateway;
    private GoogleCalendarAppointmentSyncService $sync;

    protected function setUp(): void
    {
        $this->integrations = new InMemorySharedCalendarRepository();
        $this->integrations->integration = self::integration();
        $this->ticketCalendar = new RecordingCalendarRepository();
        $this->gateway = new RecordingGoogleCalendarGateway();
        $this->sync = new GoogleCalendarAppointmentSyncService(
            $this->integrations,
            $this->ticketCalendar,
            $this->gateway,
            ['timezone' => 'America/Merida', 'default_duration_minutes' => 60, 'app_url' => 'https://example.test']
        );
    }

    public function test_tickets_with_different_assignees_use_the_same_shared_calendar(): void
    {
        $this->sync->sync(self::ticket(101, 12));
        $this->sync->sync(self::ticket(102, 88));

        self::assertCount(2, $this->gateway->created);
        self::assertSame(['org-calendar', 'org-calendar'], array_map(static fn (array $call): string => $call['connection']->calendarId, $this->gateway->created));
        self::assertSame('synced', $this->ticketCalendar->updates[0][3]);
        self::assertSame('org-calendar', $this->ticketCalendar->updates[0][5]);
    }

    public function test_retry_updates_the_stable_event_instead_of_inserting_another(): void
    {
        $ticket = self::ticket(101, 12);
        $this->sync->sync($ticket);
        $firstWrite = $this->ticketCalendar->updates[0];
        $retryTicket = self::ticket(101, 12, [
            'google_calendar_event_id' => $firstWrite[1],
            'google_calendar_event_link' => $firstWrite[2],
            'google_calendar_target_id' => 'org-calendar',
            'google_calendar_migration_status' => 'migrated',
        ]);

        $this->sync->sync($retryTicket);

        self::assertCount(1, $this->gateway->created);
        self::assertCount(1, $this->gateway->updated);
        self::assertSame($firstWrite[1], $this->gateway->updated[0]['eventId']);
    }

    public function test_reassignment_keeps_the_existing_common_event(): void
    {
        $previous = self::ticket(101, 12, [
            'google_calendar_event_id' => 'stable-event',
            'google_calendar_target_id' => 'org-calendar',
            'google_calendar_sync_status' => 'synced',
        ]);
        $current = self::ticket(101, 88, [
            'google_calendar_event_id' => 'stable-event',
            'google_calendar_target_id' => 'org-calendar',
            'google_calendar_sync_status' => 'synced',
        ]);

        $this->sync->syncReassignment($previous, $current);

        self::assertCount(1, $this->gateway->updated);
        self::assertSame([], $this->gateway->deleted);
        self::assertSame([], $this->gateway->created);
    }

    public function test_rejected_shared_authorization_marks_connection_and_ticket_for_reconnection(): void
    {
        $this->gateway->authorizationException = new GoogleCalendarAuthorizationException();

        $this->sync->sync(self::ticket(101, 12));

        self::assertSame('token_expired', $this->integrations->integration->status);
        self::assertSame('token_expired', $this->ticketCalendar->updates[0][3]);
        self::assertStringContainsString('reconexión', $this->ticketCalendar->updates[0][4]);
    }

    public function test_clearing_appointment_deletes_the_event_from_the_shared_calendar(): void
    {
        $ticket = self::ticket(101, 12, [
            'appointment_date' => null,
            'appointment_time' => null,
            'google_calendar_event_id' => 'stable-event',
            'google_calendar_target_id' => 'org-calendar',
        ]);

        $this->sync->sync($ticket);

        self::assertSame(['stable-event'], $this->gateway->deleted);
        self::assertNull($this->ticketCalendar->updates[0][1]);
    }

    private static function ticket(int $id, int $assigneeId, array $overrides = []): Ticket
    {
        return Ticket::fromArray(array_replace([
            'id' => $id,
            'customer_id' => 1,
            'consultation_type' => 'Soporte',
            'status' => 'Nuevo',
            'origin_type' => 'admin',
            'message' => 'Prueba de cita',
            'assigned_user_id' => $assigneeId,
            'customer_name' => 'Cliente',
            'appointment_date' => '2026-10-01',
            'appointment_time' => '10:00:00',
        ], $overrides));
    }

    private static function integration(): GoogleCalendarIntegration
    {
        return new GoogleCalendarIntegration('google-id', 'shared@example.test', 'access', 'refresh', null, 'org-calendar', 'calendar.events', 'connected', null, null, null);
    }
}

final class InMemorySharedCalendarRepository implements GoogleCalendarIntegrationRepository
{
    public ?GoogleCalendarIntegration $integration = null;

    public function findShared(): ?GoogleCalendarIntegration { return $this->integration; }
    public function saveShared(array $data): ?GoogleCalendarIntegration { return $this->integration; }
    public function markSharedError(string $status, string $error): bool
    {
        if ($this->integration) {
            $integration = $this->integration;
            $this->integration = new GoogleCalendarIntegration($integration->googleUserId, $integration->googleEmail, $integration->accessTokenEncrypted, $integration->refreshTokenEncrypted, $integration->tokenExpiresAt, $integration->calendarId, $integration->scopes, $status, $error, $integration->connectedAt, $integration->disconnectedAt);
        }
        return true;
    }
    public function disconnectShared(): bool { return true; }
}

final class RecordingCalendarRepository implements TicketCalendarRepository
{
    public array $updates = [];

    public function updateCalendarSync(int $ticketId, ?string $eventId, ?string $eventLink, string $status, ?string $error = null, ?string $targetCalendarId = null, ?string $migrationStatus = null, ?string $legacyCleanupStatus = null, ?string $legacyCleanupError = null, ?string $legacyEventId = null, ?string $legacyCalendarId = null, ?int $legacyUserId = null): bool
    {
        $this->updates[] = func_get_args();
        return true;
    }
}

final class RecordingGoogleCalendarGateway implements GoogleCalendarGateway
{
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];
    public ?GoogleCalendarAuthorizationException $authorizationException = null;

    public function authorizationUrl(string $state): string { return 'https://google.test/auth?state=' . $state; }
    public function exchangeCode(string $code): array { throw new \LogicException('Not used in this test.'); }
    public function createEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, array $data, ?string $eventId = null): Event
    {
        if ($this->authorizationException) throw $this->authorizationException;
        $this->created[] = ['connection' => $connection, 'eventId' => $eventId, 'data' => $data];
        return new Event(['id' => $eventId ?: 'generated-event', 'htmlLink' => 'https://calendar.test/event']);
    }
    public function updateEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId, array $data): Event
    {
        if ($this->authorizationException) throw $this->authorizationException;
        $this->updated[] = ['connection' => $connection, 'eventId' => $eventId, 'data' => $data];
        return new Event(['id' => $eventId, 'htmlLink' => 'https://calendar.test/event']);
    }
    public function deleteEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId): void
    {
        if ($this->authorizationException) throw $this->authorizationException;
        $this->deleted[] = $eventId;
    }
    public function revoke(GoogleCalendarConnection|GoogleCalendarIntegration $connection): void {}
}

final class GoogleCalendarAdminAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        $_SESSION = [];
    }

    public function test_operator_cannot_start_shared_calendar_authorization(): void
    {
        $_SESSION = [];
        $session = new SessionManager();
        $session->authenticate(['id' => 22, 'role' => 'agent']);
        $controller = new GoogleCalendarController(new InMemorySharedCalendarRepository(), new RecordingGoogleCalendarGateway(), new CsrfService($session), $session, new ViewRenderer('/tmp'));

        $response = $controller->connect();

        self::assertSame(403, $response->status());
    }

    public function test_administrator_can_start_shared_calendar_authorization(): void
    {
        $_SESSION = [];
        $session = new SessionManager();
        $session->authenticate(['id' => 1, 'role' => 'admin']);
        $controller = new GoogleCalendarController(new InMemorySharedCalendarRepository(), new RecordingGoogleCalendarGateway(), new CsrfService($session), $session, new ViewRenderer('/tmp'));

        $response = $controller->connect();

        self::assertSame(302, $response->status());
        self::assertStringContainsString('google.test/auth', $response->headers()['Location']);
        self::assertNotEmpty($_SESSION['google_calendar_oauth_state'] ?? '');
    }
}
