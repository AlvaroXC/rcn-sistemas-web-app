<?php
/*
 * Botón "Renovar" para licencias anuales y mensuales. Requiere en el ámbito:
 * $renewId, $renewType, $renewExpiresAt, $renewReturnTo, $today, $csrf y $baseUrl.
 */
use RcnSistemas\Services\Licenses\LicenseCatalog;

if (isset(LicenseCatalog::PERIOD_DAYS[$renewType])):
    $renewDate = static fn (string $date): string => date('d/m/Y', strtotime($date));
    $renewNext = LicenseCatalog::nextExpiration($renewType, $renewExpiresAt, $today);
    $renewMessage = ($renewExpiresAt ? 'Vence el ' . $renewDate($renewExpiresAt) : 'No tiene vencimiento') . '. Nuevo vencimiento: ' . $renewDate($renewNext) . '.';
?><form method="post" action="<?php echo $baseUrl; ?>/admin/licencias/<?php echo (int) $renewId; ?>/renovar" class="inline-form" data-confirm="<?php echo htmlspecialchars($renewMessage, ENT_QUOTES, 'UTF-8'); ?>" data-confirm-title="Renovar licencia" data-confirm-accept="Renovar"><input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="expected_expires_at" value="<?php echo htmlspecialchars((string) $renewExpiresAt, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="return_to" value="<?php echo htmlspecialchars($renewReturnTo, ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="<?php echo $renewClass ?? 'table__action table__action-button'; ?>">Renovar</button></form><?php
endif;
