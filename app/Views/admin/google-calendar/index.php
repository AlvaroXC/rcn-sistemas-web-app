<?php $escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); ?>
<section class="section">
    <header class="admin-header"><div><h1>Google Calendar</h1><p>Conecta tu calendario para sincronizar las citas asignadas a ti.</p></div></header>
    <?php if ($message || isset($_GET['connected'])): ?><p class="alert alert--success"><?php echo $escape($message ?: 'Google Calendar fue conectado correctamente.'); ?></p><?php endif; ?>
    <?php if ($error || isset($_GET['error'])): ?><p class="alert alert--error"><?php echo $escape($error ?: ($_GET['error'] === 'cancelled' ? 'La autorización fue cancelada.' : $_GET['error'])); ?></p><?php endif; ?>
    <div class="card panel-card">
        <?php if ($connection && $connection->status === 'connected'): ?>
            <h2>Cuenta conectada</h2><p><?php echo $escape($connection->googleEmail ?: 'Cuenta de Google'); ?></p>
            <form method="post"><input type="hidden" name="_csrf_token" value="<?php echo $escape($csrf); ?>"><input type="hidden" name="action" value="disconnect"><button type="submit" class="btn btn--secondary">Desconectar Google Calendar</button></form>
        <?php else: ?>
            <h2>Conecta tu cuenta</h2><p>Las citas se crearán en tu calendario principal con una duración predeterminada de 60 minutos.</p><a href="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/google-calendar/connect'); ?>" class="btn">Conectar Google Calendar</a>
        <?php endif; ?>
    </div>
</section>
