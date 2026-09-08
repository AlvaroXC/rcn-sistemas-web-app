<div class="form__group">
    <label class="form__label" for="<?php echo htmlspecialchars((string) ($id ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars((string) ($label ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </label>
    <input
        type="<?php echo htmlspecialchars((string) ($type ?? 'text'), ENT_QUOTES, 'UTF-8'); ?>"
        id="<?php echo htmlspecialchars((string) ($id ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
        name="<?php echo htmlspecialchars((string) ($name ?? $id ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
        value="<?php echo htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
        class="form__input"
        <?php echo !empty($required) ? 'required' : ''; ?>
    >
</div>
