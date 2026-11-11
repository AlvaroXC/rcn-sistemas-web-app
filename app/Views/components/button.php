<a
    href="<?php echo htmlspecialchars((string) ($url ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"
    class="btn<?php echo !empty($secondary) ? ' btn--secondary' : ''; ?>"
>
    <?php echo htmlspecialchars((string) ($label ?? 'Continuar'), ENT_QUOTES, 'UTF-8'); ?>
</a>
