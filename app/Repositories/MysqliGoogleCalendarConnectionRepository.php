<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RuntimeException;
use RcnSistemas\Models\GoogleCalendarConnection;

final class MysqliGoogleCalendarConnectionRepository implements GoogleCalendarConnectionRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function findByUserId(int $userId): ?GoogleCalendarConnection
    {
        $statement = $this->connection->prepare('SELECT * FROM google_calendar_connections WHERE user_id = ? LIMIT 1');
        if (!$statement) throw new RuntimeException('No se pudo consultar la conexión de Google Calendar. Ejecuta las migraciones pendientes.');
        $statement->bind_param('i', $userId); $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        return $row ? GoogleCalendarConnection::fromArray($row) : null;
    }

    public function save(array $data): ?GoogleCalendarConnection
    {
        $userId = (int) $data['user_id']; $googleUserId = $data['google_user_id'] ?? null; $googleEmail = $data['google_email'] ?? null;
        $access = $data['access_token_encrypted'] ?? null; $refresh = $data['refresh_token_encrypted'] ?? null; $expires = $data['token_expires_at'] ?? null;
        $calendarId = (string) ($data['calendar_id'] ?? 'primary'); $scopes = (string) ($data['scopes'] ?? ''); $status = (string) ($data['status'] ?? 'connected');
        $statement = $this->connection->prepare('INSERT INTO google_calendar_connections (user_id, google_user_id, google_email, access_token_encrypted, refresh_token_encrypted, token_expires_at, calendar_id, scopes, status, last_error, connected_at, disconnected_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP, NULL) ON DUPLICATE KEY UPDATE google_user_id=VALUES(google_user_id), google_email=VALUES(google_email), access_token_encrypted=VALUES(access_token_encrypted), refresh_token_encrypted=COALESCE(VALUES(refresh_token_encrypted), refresh_token_encrypted), token_expires_at=VALUES(token_expires_at), calendar_id=VALUES(calendar_id), scopes=VALUES(scopes), status=VALUES(status), last_error=NULL, connected_at=CURRENT_TIMESTAMP, disconnected_at=NULL');
        if (!$statement) throw new RuntimeException('No se pudo guardar la conexión de Google Calendar. Ejecuta las migraciones pendientes.');
        $statement->bind_param('issssssss', $userId, $googleUserId, $googleEmail, $access, $refresh, $expires, $calendarId, $scopes, $status);
        if (!$statement->execute()) return null;
        return $this->findByUserId($userId);
    }

    public function markError(int $userId, string $status, string $error): bool
    {
        $statement = $this->connection->prepare('UPDATE google_calendar_connections SET status=?, last_error=? WHERE user_id=?');
        if (!$statement) throw new RuntimeException('No se pudo actualizar el estado de Google Calendar. Ejecuta las migraciones pendientes.');
        $statement->bind_param('ssi', $status, $error, $userId); return $statement->execute();
    }

    public function disconnect(int $userId): bool
    {
        $statement = $this->connection->prepare('UPDATE google_calendar_connections SET status="disconnected", access_token_encrypted=NULL, refresh_token_encrypted=NULL, last_error=NULL, disconnected_at=CURRENT_TIMESTAMP WHERE user_id=?');
        if (!$statement) throw new RuntimeException('No se pudo desconectar Google Calendar. Ejecuta las migraciones pendientes.');
        $statement->bind_param('i', $userId); return $statement->execute();
    }
}
