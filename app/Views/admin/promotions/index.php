<section class="section">
    <header class="admin-header">
        <h1>Gestionar promociones</h1>
        <?php if ($action === 'list'): ?><a href="promotions.php?action=new" class="btn">Nueva promoción</a><?php else: ?><a href="promotions.php" class="btn btn--secondary">Volver</a><?php endif; ?>
    </header>
    <?php if (!empty($error)): ?><p class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if ($action === 'list'): ?>
        <?php if (!$promotions): ?><p>No hay promociones registradas.</p><?php else: ?><table class="table"><thead><tr><th>Título</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($promotions as $item): ?><tr><td><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo $item->active ? 'Activa' : 'Inactiva'; ?></td><td><a href="promotions.php?action=edit&amp;id=<?php echo $item->id; ?>">Editar</a><form method="post" class="inline-form"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?php echo $item->id; ?>"><button type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table><?php endif; ?>
    <?php else: ?>
        <form method="post" class="admin-form">
            <input type="hidden" name="form_action" value="save"><input type="hidden" name="id" value="<?php echo (int) ($promotion?->id ?? 0); ?>"><input type="hidden" name="current_img" value="<?php echo htmlspecialchars($promotion?->imagePath ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <label>Título<input name="title" required value="<?php echo htmlspecialchars($promotion?->title ?? '', ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Descripción corta<textarea name="short_description" required><?php echo htmlspecialchars($promotion?->shortDescription ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
            <label>Descripción completa<textarea name="full_description" required><?php echo htmlspecialchars($promotion?->fullDescription ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
            <label>Términos y condiciones<textarea name="terms_conditions" required><?php echo htmlspecialchars($promotion?->termsConditions ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
            <label>Inicio<input type="date" name="start_date" required value="<?php echo htmlspecialchars($promotion?->startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Fin<input type="date" name="end_date" required value="<?php echo htmlspecialchars($promotion?->endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label><input type="checkbox" name="active" <?php echo ($promotion?->active ?? true) ? 'checked' : ''; ?>> Activa</label>
            <button type="submit" class="btn">Guardar</button>
        </form>
    <?php endif; ?>
</section>
