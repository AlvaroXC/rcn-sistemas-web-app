<?php
use RcnSistemas\Services\Licenses\QuotationCatalog;

$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$licenseUrl = $baseUrl . '/admin/licencias/' . (int) $license->id . '/editar';
?>
<section class="section">
    <nav class="breadcrumbs" aria-label="Navegación"><a href="<?php echo $baseUrl; ?>/admin/licencias">Licencias</a><span>/</span><a href="<?php echo $licenseUrl; ?>"><?php echo $escape($license->alias ?: $license->productName); ?><?php echo $customer ? ' · ' . $escape($customer->name) : ''; ?></a><span>/</span><span><?php echo $quotation ? 'Editar cotización' : 'Nueva cotización'; ?></span></nav>
    <header class="admin-header"><div><h1><?php echo $quotation ? 'Editar cotización ' . $escape($quotation->folio) : 'Nueva cotización'; ?></h1><p>Cotización de renovación de <?php echo $escape($license->productName); ?><?php echo $license->alias ? ' «' . $escape($license->alias) . '»' : ''; ?><?php echo $license->serialNumber ? ' (serie ' . $escape($license->serialNumber) . ')' : ''; ?>. Facturarla no cambia el vencimiento: para eso usa Renovar en la licencia.</p></div><div class="ticket-header-actions"><a href="<?php echo $licenseUrl; ?>" class="btn btn--secondary">Volver</a></div></header>
    <?php if ($error): ?><p class="alert alert--error"><?php echo $escape($error); ?></p><?php endif; ?>
    <div class="card panel-card"><form method="post" class="admin-form"><input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>">
        <div class="admin-form__row admin-form__row--triple">
            <div class="admin-form__group"><label class="admin-form__label" for="folio">Folio</label><input id="folio" name="folio" class="admin-form__input" maxlength="50" required value="<?php echo $escape($values['folio']); ?>"></div>
            <div class="admin-form__group"><label class="admin-form__label" for="quoted_at">Fecha</label><input id="quoted_at" name="quoted_at" type="date" class="admin-form__input" required value="<?php echo $escape($values['quoted_at']); ?>"></div>
            <div class="admin-form__group"><label class="admin-form__label" for="status">Estado</label><?php if ($quotation): ?><select id="status" name="status" class="admin-form__input"><?php foreach (QuotationCatalog::options($quotation->status) as $value => $label): ?><option value="<?php echo $escape($value); ?>" <?php echo $values['status'] === $value ? 'selected' : ''; ?>><?php echo $escape($label); ?></option><?php endforeach; ?></select><?php else: ?><input id="status" class="admin-form__input" value="Enviada" disabled><span class="ticket-muted">Toda cotización nueva empieza como Enviada.</span><?php endif; ?></div>
        </div>
        <div class="admin-form__group"><label class="admin-form__label" for="notes">Observaciones</label><textarea id="notes" name="notes" class="admin-form__textarea" rows="3" maxlength="5000"><?php echo $escape($values['notes']); ?></textarea><span class="ticket-muted">Si el cliente rechaza la cotización, registra aquí el motivo (obligatorio).</span></div>
        <div class="admin-form__buttons"><button type="submit" class="btn"><?php echo $quotation ? 'Guardar cambios' : 'Registrar cotización'; ?></button><?php if ($quotation): ?><button type="submit" form="delete-quotation" class="btn btn--danger">Eliminar cotización</button><?php endif; ?></div>
    </form>
    <?php if ($quotation): ?><form id="delete-quotation" method="post" action="<?php echo $baseUrl; ?>/admin/cotizaciones/<?php echo (int) $quotation->id; ?>/eliminar" hidden data-confirm="¿Seguro que deseas eliminar esta cotización?" data-confirm-title="Eliminar cotización" data-confirm-accept="Eliminar" data-confirm-variant="danger"><input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>"></form><?php endif; ?>
    </div>
</section>
