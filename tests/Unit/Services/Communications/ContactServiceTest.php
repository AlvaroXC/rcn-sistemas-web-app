<?php

namespace Tests\Unit\Services\Communications;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Services\Communications\ContactService;
use RcnSistemas\Services\Communications\MailService;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;
use Tests\Unit\Services\Customers\InMemoryCustomerRepository;
use Tests\Unit\Services\Tickets\InMemoryTicketRepository;

final class ContactServiceTest extends TestCase
{
    public function test_it_escapes_contact_values_in_the_mail_body(): void
    {
        $service = new ContactService(
            new CustomerService(new InMemoryCustomerRepository(), new CustomerIdentity()),
            new TicketService(new InMemoryTicketRepository(), new TicketWorkflow()),
            new UploadService(),
            new MailService(['contact_address' => 'test@example.com'])
        );

        $body = $service->buildMailBody(
            Ticket::fromArray(['id' => 2, 'consultation_type' => 'Soporte', 'message' => '<script>alert(1)</script>']),
            '<Cliente>',
            'client@example.com',
            '999',
            []
        );

        self::assertStringNotContainsString('<script>', $body);
        self::assertStringContainsString('&lt;Cliente&gt;', $body);
    }
}
