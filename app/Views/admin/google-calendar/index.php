<?php
$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$connectionStatus = $connection?->status ?? 'disconnected';
$isConnected = $connectionStatus === 'connected';
$needsReconnect = $connectionStatus === 'token_expired';
$status = match (true) {
    $isConnected => ['label' => 'Conectado', 'class' => 'is-connected', 'icon' => 'fa-check'],
    $needsReconnect => ['label' => 'Requiere reconexión', 'class' => 'is-warning', 'icon' => 'fa-rotate'],
    default => ['label' => 'No conectado', 'class' => 'is-disconnected', 'icon' => 'fa-link-slash'],
};
$connectedAt = null;
if ($connection?->connectedAt) {
    try {
        $connectedAt = (new \DateTimeImmutable($connection->connectedAt))->format('d/m/Y');
    } catch (\Throwable) {
        $connectedAt = null;
    }
}
$queryError = (string) ($_GET['error'] ?? '');
$errorMessage = $queryError === 'cancelled'
    ? 'La autorización fue cancelada. Tu configuración anterior no fue modificada.'
    : (($error || $queryError !== '') ? 'No pudimos completar la operación con Google Calendar. Intenta nuevamente.' : null);
?>
<section class="section google-calendar-page">
    <header class="admin-header google-calendar-page__header">
        <div>
            <p class="google-calendar-page__eyebrow"><i class="fas fa-plug" aria-hidden="true"></i> Integración</p>
            <h1>Google Calendar</h1>
            <p>Sincroniza automáticamente las citas asignadas a ti con tu calendario.</p>
        </div>
        <span class="google-calendar-status google-calendar-status--<?php echo $status['class']; ?>"><i class="fas <?php echo $status['icon']; ?>" aria-hidden="true"></i><?php echo $status['label']; ?></span>
    </header>

    <?php if ($message || isset($_GET['connected'])): ?><p class="alert alert--success google-calendar-alert" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><?php echo $escape($message ?: 'Google Calendar fue conectado correctamente.'); ?></p><?php endif; ?>
    <?php if ($errorMessage): ?><p class="alert alert--error google-calendar-alert" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><?php echo $escape($errorMessage); ?></p><?php endif; ?>

    <div class="google-calendar-grid">
        <article class="card google-calendar-card google-calendar-card--connection">
            <div class="google-calendar-card__heading">
                <div class="google-calendar-card__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></div>
                <div>
                    <p class="google-calendar-card__eyebrow">Cuenta de Google</p>
                    <h2><?php echo $isConnected ? 'Tu calendario está listo' : ($needsReconnect ? 'Tu conexión necesita atención' : 'Conecta tu cuenta'); ?></h2>
                </div>
            </div>

            <?php if ($isConnected): ?>
                <div class="google-calendar-account">
                    <div class="google-calendar-account__avatar" aria-hidden="true"><i class="fas fa-user"></i></div>
                    <div class="google-calendar-account__details">
                        <strong><?php echo $escape($connection->googleEmail ?: 'Cuenta de Google'); ?></strong>
                        <span>Calendario principal</span>
                    </div>
                    <?php if ($connectedAt): ?><small>Conectado el <?php echo $escape($connectedAt); ?></small><?php endif; ?>
                </div>
                <p class="google-calendar-card__description">Las citas nuevas y sus cambios se sincronizarán con esta cuenta.</p>
                <div class="google-calendar-card__actions">
                    <a href="https://calendar.google.com/calendar/u/0/r" class="btn" target="_blank" rel="noopener"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Abrir Google Calendar</a>
                    <form method="post" onsubmit="return confirm('¿Seguro que deseas desconectar Google Calendar?');"><input type="hidden" name="_csrf_token" value="<?php echo $escape($csrf); ?>"><input type="hidden" name="action" value="disconnect"><button type="submit" class="btn btn--secondary"><i class="fas fa-link-slash" aria-hidden="true"></i> Desconectar</button></form>
                </div>
            <?php elseif ($needsReconnect): ?>
                <div class="google-calendar-state google-calendar-state--warning"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><div><strong>Vuelve a conectar tu cuenta</strong><p>Google requiere autorización nuevamente. Tus citas locales se conservarán y volverán a sincronizarse después de reconectar.</p></div></div>
                <a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn"><i class="fas fa-rotate" aria-hidden="true"></i> Reconectar Google Calendar</a>
            <?php else: ?>
                <p class="google-calendar-card__description">Las citas se crearán en tu calendario principal con una duración predeterminada de 60 minutos.</p>
                <div class="google-calendar-permissions"><div><i class="fas fa-calendar-plus" aria-hidden="true"></i><span>Crear y actualizar eventos de tus citas</span></div><div><i class="fas fa-shield-halved" aria-hidden="true"></i><span>Conexión segura y revocable en cualquier momento</span></div></div>
                <a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn"><i class="fas fa-link" aria-hidden="true"></i> Conectar Google Calendar</a>
            <?php endif; ?>
        </article>

        <aside class="card google-calendar-card google-calendar-card--how-it-works">
            <div class="google-calendar-card__heading"><div class="google-calendar-card__icon google-calendar-card__icon--secondary" aria-hidden="true"><i class="fas fa-route"></i></div><div><p class="google-calendar-card__eyebrow">Así funciona</p><h2>Tu agenda, siempre al día</h2></div></div>
            <ol class="google-calendar-steps">
                <li><span>1</span><div><strong>Conecta tu cuenta</strong><p>Autoriza a RCN Sistemas para gestionar los eventos de tus citas.</p></div></li>
                <li><span>2</span><div><strong>Asigna una cita</strong><p>Al guardar fecha y hora en un ticket, se prepara el evento automáticamente.</p></div></li>
                <li><span>3</span><div><strong>Los cambios se sincronizan</strong><p>Si editas o eliminas la cita, Google Calendar se actualiza también.</p></div></li>
            </ol>
            <div class="google-calendar-privacy"><i class="fas fa-lock" aria-hidden="true"></i><p><strong>Privacidad primero.</strong> Solo solicitamos permisos para administrar eventos; no leemos ni modificamos tus contactos.</p></div>
        </aside>
    </div>
</section>
