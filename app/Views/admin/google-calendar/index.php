<?php
$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$connectionStatus = $connection?->status ?? 'disconnected';
$isConnected = $connectionStatus === 'connected';
$needsReconnect = $connectionStatus === 'token_expired';
$hasConfigurationError = $connectionStatus === 'configuration_error';
$status = match (true) {
    $isConnected => ['label' => 'Conectado', 'class' => 'is-connected', 'icon' => 'fa-check'],
    $needsReconnect, $hasConfigurationError => ['label' => $hasConfigurationError ? 'Revisar configuración' : 'Requiere reconexión', 'class' => 'is-warning', 'icon' => 'fa-rotate'],
    default => ['label' => 'No conectado', 'class' => 'is-disconnected', 'icon' => 'fa-link-slash'],
};
$connectedAt = null;
if ($connection?->connectedAt) {
    try { $connectedAt = (new \DateTimeImmutable($connection->connectedAt))->format('d/m/Y'); } catch (\Throwable) {}
}
$queryError = (string) ($_GET['error'] ?? '');
$errorMessage = match ($queryError) {
    'cancelled' => 'La autorización fue cancelada. La configuración anterior no fue modificada.',
    'invalid_state' => 'La autorización no pudo validarse. Inicia la conexión nuevamente.',
    default => (($error || $queryError !== '') ? 'No se pudo completar la operación con Google Calendar. Revisa la configuración e intenta nuevamente.' : null),
};
?>
<section class="section google-calendar-page">
    <header class="admin-header google-calendar-page__header">
        <div>
            <p class="google-calendar-page__eyebrow"><i class="fas fa-plug" aria-hidden="true"></i> Integración de la organización</p>
            <h1>Google Calendar</h1>
            <p>Administradores y operadores sincronizan las citas autorizadas en el calendario común.</p>
        </div>
        <span class="google-calendar-status google-calendar-status--<?php echo $status['class']; ?>"><i class="fas <?php echo $status['icon']; ?>" aria-hidden="true"></i><?php echo $status['label']; ?></span>
    </header>

    <?php if ($message): ?><p class="alert alert--success google-calendar-alert" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><?php echo $escape($message); ?></p><?php endif; ?>
    <?php if ($errorMessage): ?><p class="alert alert--error google-calendar-alert" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><?php echo $escape($errorMessage); ?></p><?php endif; ?>

    <div class="google-calendar-grid">
        <article class="card google-calendar-card google-calendar-card--connection">
            <div class="google-calendar-card__heading">
                <div class="google-calendar-card__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></div>
                <div>
                    <p class="google-calendar-card__eyebrow">Calendario compartido</p>
                    <h2><?php echo $isConnected ? 'La integración está lista' : ($needsReconnect ? 'La autorización requiere atención' : ($hasConfigurationError ? 'Revisa las credenciales' : 'Conecta el calendario de la organización')); ?></h2>
                </div>
            </div>

            <?php if ($isConnected): ?>
                <div class="google-calendar-account">
                    <div class="google-calendar-account__avatar" aria-hidden="true"><i class="fas fa-users"></i></div>
                    <div class="google-calendar-account__details">
                        <strong><?php echo $escape($connection->googleEmail ?: 'Cuenta central de Google'); ?></strong>
                        <span>Calendario: <?php echo $escape($connection->calendarId); ?></span>
                    </div>
                    <?php if ($connectedAt): ?><small>Conectado el <?php echo $escape($connectedAt); ?></small><?php endif; ?>
                </div>
                <p class="google-calendar-card__description">Las citas de tickets autorizados se sincronizan aquí, sin importar quién esté asignado.</p>
                <?php if ($isAdmin): ?>
                    <div class="google-calendar-card__actions">
                        <a href="https://calendar.google.com/calendar/" class="btn" target="_blank" rel="noopener"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Abrir Google Calendar</a>
                        <a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn btn--secondary"><i class="fas fa-rotate" aria-hidden="true"></i> Reconectar</a>
                        <form method="post" onsubmit="return confirm('¿Seguro que deseas desconectar el calendario común?');"><input type="hidden" name="_csrf_token" value="<?php echo $escape($csrf); ?>"><input type="hidden" name="action" value="disconnect"><button type="submit" class="btn btn--secondary"><i class="fas fa-link-slash" aria-hidden="true"></i> Desconectar</button></form>
                    </div>
                <?php endif; ?>
            <?php elseif ($needsReconnect || $hasConfigurationError): ?>
                <div class="google-calendar-state google-calendar-state--warning"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><div><strong><?php echo $hasConfigurationError ? 'La configuración OAuth necesita revisión' : 'Google requiere autorización nuevamente'; ?></strong><p>Las citas locales se conservan. Después de corregir la conexión, se pueden reintentar desde los tickets.</p></div></div>
                <?php if ($isAdmin): ?><a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn"><i class="fas fa-rotate" aria-hidden="true"></i> Reconectar calendario común</a><?php else: ?><p class="google-calendar-card__description">Solicita a un administrador que reconecte el calendario común.</p><?php endif; ?>
            <?php else: ?>
                <p class="google-calendar-card__description">Una persona administradora debe conectar la cuenta de Google de la organización. Administradores y operadores podrán usarla desde sus tickets autorizados.</p>
                <?php if ($isAdmin): ?><a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn"><i class="fas fa-link" aria-hidden="true"></i> Conectar calendario común</a><?php endif; ?>
            <?php endif; ?>
        </article>

        <aside class="card google-calendar-card google-calendar-card--how-it-works">
            <div class="google-calendar-card__heading"><div class="google-calendar-card__icon google-calendar-card__icon--secondary" aria-hidden="true"><i class="fas fa-route"></i></div><div><p class="google-calendar-card__eyebrow">Así funciona</p><h2>Un calendario para el equipo</h2></div></div>
            <ol class="google-calendar-steps">
                <li><span>1</span><div><strong>Un administrador conecta Google</strong><p>La autorización se administra una vez para la organización.</p></div></li>
                <li><span>2</span><div><strong>Admin u operador guarda una cita</strong><p>Se sincroniza si tiene acceso al ticket.</p></div></li>
                <li><span>3</span><div><strong>Los cambios llegan al mismo calendario</strong><p>Editar o reintentar conserva un evento por ticket.</p></div></li>
            </ol>
            <div class="google-calendar-privacy"><i class="fas fa-lock" aria-hidden="true"></i><p><strong>Privacidad primero.</strong> La conexión central solo administra eventos y no amplía los permisos de acceso a tickets.</p></div>
        </aside>
    </div>
</section>
