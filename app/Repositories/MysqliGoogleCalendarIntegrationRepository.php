<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RuntimeException;
use RcnSistemas\Models\GoogleCalendarIntegration;

final class MysqliGoogleCalendarIntegrationRepository implements GoogleCalendarIntegrationRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function findShared(): ?GoogleCalendarIntegration
    {
        $result = $this->connection->query('SELECT * FROM google_calendar_integrations WHERE id = 1 LIMIT 1');
        if (!$result) throw new RuntimeException('No se pudo consultar la integración común de Google Calendar. Ejecuta las migraciones pendientes.');
        $row = $result->fetch_assoc();
        return $row ? GoogleCalendarIntegration::fromArray($row) : null;
    }

    public function saveShared(array $data): ?GoogleCalendarIntegration
    {
        $googleUserId = $data['google_user_id'] ?? null;
        $googleEmail = $data['google_email'] ?? null;
        $access = $data['access_token_encrypted'] ?? null;
        $refresh = $data['refresh_token_encrypted'] ?? null;
        $expires = $data['token_expires_at'] ?? null;
        $calendarId = (string) ($data['calendar_id'] ?? 'primary');
        $scopes = (string) ($data['scopes'] ?? '');
        $status = (string) ($data['status'] ?? 'connected');
        if ($refresh === null || $refresh === '') throw new RuntimeException('Google no entregó un token de renovación. Vuelve a autorizar la integración.');

        $statement = $this->connection->prepare('INSERT INTO google_calendar_integrations (id, google_user_id, google_email, access_token_encrypted, refresh_token_encrypted, token_expires_at, calendar_id, scopes, status, last_error, connected_at, disconnected_at) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP, NULL) ON DUPLICATE KEY UPDATE google_user_id=VALUES(google_user_id), google_email=VALUES(google_email), access_token_encrypted=VALUES(access_token_encrypted), refresh_token_encrypted=VALUES(refresh_token_encrypted), token_expires_at=VALUES(token_expires_at), calendar_id=VALUES(calendar_id), scopes=VALUES(scopes), status=VALUES(status), last_error=NULL, connected_at=CURRENT_TIMESTAMP, disconnected_at=NULL');
        if (!$statement) throw new RuntimeException('No se pudo guardar la integración común de Google Calendar. Ejecuta las migraciones pendientes.');
        $statement->bind_param('ssssssss', $googleUserId, $googleEmail, $access, $refresh, $expires, $calendarId, $scopes, $status);
        if (!$statement->execute()) return null;
        return $this->findShared();
    }

    public function markSharedError(string $status, string $error): bool
    {
        $statement = $this->connection->prepare('UPDATE google_calendar_integrations SET status=?, last_error=? WHERE id=1');
        if (!$statement) throw new RuntimeException('No se pudo actualizar el estado de Google Calendar.');
        $statement->bind_param('ss', $status, $error);
        return $statement->execute();
    }

    public function disconnectShared(): bool
    {
        $statement = $this->connection->prepare('UPDATE google_calendar_integrations SET status="disconnected", access_token_encrypted=NULL, refresh_token_encrypted=NULL, last_error=NULL, disconnected_at=CURRENT_TIMESTAMP WHERE id=1');
        if (!$statement) throw new RuntimeException('No se pudo desconectar la integración común de Google Calendar.');
        return $statement->execute();
    }
}
