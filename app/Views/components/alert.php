<?php if (!empty($message)): ?>
    <div class="alert alert--<?php echo htmlspecialchars((string) ($type ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>
