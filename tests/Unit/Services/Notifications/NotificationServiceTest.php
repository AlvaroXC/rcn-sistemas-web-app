<?php

namespace Tests\Unit\Services\Notifications;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Notifications\NotificationService;

final class NotificationServiceTest extends TestCase
{
    public function test_it_builds_a_ticket_assignment_notification(): void
    {
        $notification = (new NotificationService())->assignmentMessage(18, 'Administrador');

        self::assertSame('ticket_assignment', $notification['type']);
        self::assertSame(18, $notification['ticket_id']);
        self::assertStringContainsString('#18', $notification['message']);
    }
}
