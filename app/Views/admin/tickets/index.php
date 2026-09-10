<?php
$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$url = static function (array $extra = []) use ($filters): string {
    $query = array_filter(array_merge($filters, $extra), static fn ($item) => $item !== '' && $item !== null);
    return \RcnSistemas\Core\Routing\Url::to('/admin/tickets') . '?' . http_build_query($query);
};
$statusClass = static fn (string $status): string => strtolower(str_replace(' ', '-', $status));
?>
<section class="section">
    <header class="admin-header">
        <div><h1><?php echo $escape($scopeTitle); ?></h1><p><?php echo $escape($scopeDescription); ?></p></div>
        <div class="ticket-header-actions"><a href="<?php echo $baseUrl; ?>/admin/tickets/nuevo" class="btn">Nuevo ticket</a><a href="<?php echo $baseUrl; ?>/admin/clientes" class="btn btn--secondary">Ver clientes</a></div>
    </header>
    <nav class="ticket-scope-tabs" aria-label="Alcance de tickets">
        <a href="<?php echo $escape($url(['scope' => 'open', 'page' => 1])); ?>" class="ticket-scope-tab <?php echo $isOpenScope ? 'ticket-scope-tab--active' : ''; ?>">Tickets abiertos</a>
        <a href="<?php echo $escape($url(['scope' => 'all', 'page' => 1])); ?>" class="ticket-scope-tab <?php echo !$isOpenScope ? 'ticket-scope-tab--active' : ''; ?>">Todos los tickets</a>
    </nav>
    <div class="stats">
        <article class="stat-card"><i class="fas fa-life-ring stat-card__icon"></i><div class="stat-card__value"><?php echo (int) $ticketStats['total']; ?></div><p class="stat-card__label">Tickets</p></article>
        <article class="stat-card"><i class="fas fa-hourglass-half stat-card__icon"></i><div class="stat-card__value"><?php echo (int) $ticketStats['open']; ?></div><p class="stat-card__label">Tickets abiertos</p></article>
        <article class="stat-card"><i class="fas fa-user-friends stat-card__icon"></i><div class="stat-card__value"><?php echo (int) $ticketStats['customers']; ?></div><p class="stat-card__label">Clientes</p></article>
    </div>
    <div class="card panel-card ticket-filter-card">
        <form method="get" class="admin-form ticket-filter-form">
            <input type="hidden" name="scope" value="<?php echo $escape($filters['scope']); ?>">
            <div class="admin-form__row admin-form__row--triple">
                <div class="admin-form__group"><label class="admin-form__label" for="customer">Buscar ticket o cliente</label><input id="customer" name="customer" class="admin-form__input" value="<?php echo $escape($filters['customer']); ?>" placeholder="Número, nombre, correo o teléfono"></div>
                <div class="admin-form__group"><label class="admin-form__label" for="status">Estado</label><select id="status" name="status" class="admin-form__input"><option value="">Todos</option><?php foreach ($statuses as $status): ?><option value="<?php echo $escape($status); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo $escape($status); ?></option><?php endforeach; ?></select></div>
                <div class="admin-form__group"><label class="admin-form__label" for="consultation_type">Tipo</label><select id="consultation_type" name="consultation_type" class="admin-form__input"><option value="">Todos</option><?php foreach ($types as $type): ?><option value="<?php echo $escape($type); ?>" <?php echo $filters['consultation_type'] === $type ? 'selected' : ''; ?>><?php echo $escape($type); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="admin-form__row admin-form__row--triple">
                <div class="admin-form__group"><label class="admin-form__label" for="assigned_user_id">Responsable</label><select id="assigned_user_id" name="assigned_user_id" class="admin-form__input"><option value="">Todos</option><option value="unassigned" <?php echo $filters['assigned_user_id'] === 'unassigned' ? 'selected' : ''; ?>>Sin responsable</option><?php foreach ($responsibleUsers as $user): ?><option value="<?php echo (int) $user['id']; ?>" <?php echo $filters['assigned_user_id'] === (string) $user['id'] ? 'selected' : ''; ?>><?php echo $escape($user['display_name'] ?: $user['username']); ?></option><?php endforeach; ?></select></div>
                <div class="admin-form__group"><label class="admin-form__label" for="date_from">Desde</label><input id="date_from" type="date" name="date_from" class="admin-form__input" value="<?php echo $escape($filters['date_from']); ?>"></div>
                <div class="admin-form__group"><label class="admin-form__label" for="date_to">Hasta</label><input id="date_to" type="date" name="date_to" class="admin-form__input" value="<?php echo $escape($filters['date_to']); ?>"></div>
            </div>
            <div class="ticket-filter-form__footer">
                <div class="admin-form__group"><label class="admin-form__label" for="per_page">Mostrar</label><select id="per_page" name="per_page" class="admin-form__input"><?php foreach ([10, 25, 50] as $size): ?><option value="<?php echo $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo $size; ?> por página</option><?php endforeach; ?></select></div>
                <div class="ticket-filter-form__actions"><button class="btn" type="submit">Filtrar</button><a href="<?php echo $baseUrl; ?>/admin/tickets" class="btn btn--secondary">Limpiar</a></div>
            </div>
        </form>
    </div>
    <?php if ($activeFilters): ?><div class="filter-chips ticket-active-filters" aria-label="Filtros activos"><?php foreach ($activeFilters as $activeFilter): ?><span class="filter-chip"><?php echo $escape($activeFilter); ?></span><?php endforeach; ?><a class="filter-chip filter-chip--clear" href="<?php echo $baseUrl; ?>/admin/tickets">Quitar filtros</a></div><?php endif; ?>
    <div class="ticket-toolbar">
        <div class="ticket-results-summary"><strong><?php echo (int) $total; ?></strong> tickets encontrados <span class="ticket-results-summary__page">Página <?php echo (int) $page; ?> de <?php echo (int) $totalPages; ?></span></div>
        <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Paginación de tickets"><?php if ($page > 1): ?><a href="<?php echo $escape($url(['page' => $page - 1])); ?>" class="pagination__link">Anterior</a><?php endif; ?><?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?><a href="<?php echo $escape($url(['page' => $pageNumber])); ?>" class="pagination__link <?php echo $pageNumber === $page ? 'pagination__link--active' : ''; ?>"><?php echo $pageNumber; ?></a><?php endfor; ?><?php if ($page < $totalPages): ?><a href="<?php echo $escape($url(['page' => $page + 1])); ?>" class="pagination__link">Siguiente</a><?php endif; ?></nav><?php endif; ?>
    </div>
    <div class="table-wrapper">
        <table class="table table--tickets">
            <thead><tr><th>Ticket</th><th>Cliente</th><th>Responsable</th><th>Precio</th><th>Estado</th><th>Cita</th><th>Canal</th><th>Contexto</th><th>Fecha</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if (!$tickets): ?><tr><td colspan="10">No se encontraron tickets con los filtros seleccionados.</td></tr>
                <?php else: foreach ($tickets as $ticket): ?>
                    <?php $ticketUrl = \RcnSistemas\Core\Routing\Url::to('/admin/tickets/' . (int) $ticket['id']); ?>
                    <tr>
                        <td data-label="Ticket"><a href="<?php echo $escape($ticketUrl); ?>" class="ticket-primary-link">#<?php echo (int) $ticket['id']; ?></a><div class="ticket-meta"><?php echo $escape($ticket['consultation_type']); ?></div></td>
                        <td data-label="Cliente"><strong><?php echo $escape($ticket['customer_name']); ?></strong><div class="ticket-meta"><?php echo $escape($ticket['customer_phone']); ?></div></td>
                        <td data-label="Responsable"><?php echo $escape($ticket['assigned_display_name'] ?: $ticket['assigned_username'] ?: 'Sin responsable'); ?></td>
                        <td data-label="Precio"><?php echo $ticket['price_total_amount'] !== null ? '$' . number_format((float) $ticket['price_total_amount'], 2) . ' ' . $escape($ticket['price_currency'] ?: 'MXN') : 'Sin precio'; ?></td>
                        <td data-label="Estado"><span class="ticket-status ticket-status--<?php echo $escape($statusClass($ticket['status'])); ?>"><span class="ticket-status__dot"></span><?php echo $escape($ticket['status']); ?></span></td>
                        <td data-label="Cita"><?php echo $escape($ticket['appointment_date'] ?: 'Sin cita'); ?></td>
                        <td data-label="Canal"><?php echo $escape($ticket['origin_type']); ?></td>
                        <td data-label="Contexto"><?php echo $escape($ticket['context_label'] ?: 'Sin contexto adicional'); ?></td>
                        <td data-label="Fecha"><?php echo $escape(date('d/m/Y H:i', strtotime($ticket['created_at']))); ?></td>
                        <td data-label="Acciones"><a href="<?php echo $escape($ticketUrl); ?>" class="table__action">Ver ticket</a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
