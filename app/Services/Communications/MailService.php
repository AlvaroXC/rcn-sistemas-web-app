<?php

namespace RcnSistemas\Services\Communications;

use Closure;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    public function __construct(
        private readonly array $configuration,
        private readonly ?Closure $mailerFactory = null
    ) {
    }

    public function send(string $subject, string $html, ?string $attachmentPath = null): bool
    {
        $factory = $this->mailerFactory ?? static fn (): PHPMailer => new PHPMailer();
        $mailer = $factory();
        $mailer->isSMTP();
        $mailer->Host = (string) ($this->configuration['host'] ?? 'localhost');
        $mailer->SMTPAuth = true;
        $mailer->Port = (int) ($this->configuration['port'] ?? 587);
        $mailer->Username = (string) ($this->configuration['username'] ?? '');
        $mailer->Password = (string) ($this->configuration['password'] ?? '');
        $mailer->SMTPSecure = (string) ($this->configuration['secure'] ?? 'tls');
        $mailer->setFrom((string) ($this->configuration['from_address'] ?? ''), (string) ($this->configuration['from_name'] ?? 'RCN Sistemas'));
        $mailer->addAddress((string) ($this->configuration['contact_address'] ?? ''));
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->Body = $html;

        if ($attachmentPath !== null && $attachmentPath !== '') {
            $mailer->addAttachment($attachmentPath);
        }

        return $mailer->send();
    }
}
