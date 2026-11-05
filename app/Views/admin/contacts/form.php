<?php
$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$customerUrl = $baseUrl . '/admin/clientes/' . (int) $customer->id;
?>
<section class="section">
    <nav class="breadcrumbs" aria-label="Navegación"><a href="<?php echo $baseUrl; ?>/admin/clientes">Clientes</a><span>/</span><a href="<?php echo $customerUrl; ?>"><?php echo $escape($customer->name); ?></a><span>/</span><span><?php echo $contact ? 'Editar contacto' : 'Nuevo contacto'; ?></span></nav>
    <header class="admin-header"><div><h1><?php echo $contact ? 'Editar contacto' : 'Nuevo contacto'; ?></h1><p>Persona que habla en nombre de <?php echo $escape($customer->businessName ?: $customer->name); ?>, por ejemplo su secretaria o su contador.</p></div><div class="ticket-header-actions"><a href="<?php echo $customerUrl; ?>" class="btn btn--secondary">Volver</a></div></header>
    <?php if ($error): ?><p class="alert alert--error"><?php echo $escape($error); ?></p><?php endif; ?>
    <div class="card panel-card"><form method="post" class="admin-form"><input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>">
        <div class="admin-form__row admin-form__row--triple">
            <div class="admin-form__group"><label class="admin-form__label" for="name">Nombre</label><input id="name" name="name" class="admin-form__input" maxlength="150" required value="<?php echo $escape($values['name']); ?>"></div>
            <div class="admin-form__group"><label class="admin-form__label" for="phone">Teléfono</label><input id="phone" name="phone" type="tel" class="admin-form__input" maxlength="50" required value="<?php echo $escape($values['phone']); ?>"></div>
            <div class="admin-form__group"><label class="admin-form__label" for="email">Correo (opcional)</label><input id="email" name="email" type="email" class="admin-form__input" maxlength="255" value="<?php echo $escape($values['email']); ?>"></div>
        </div>
        <div class="admin-form__buttons"><button type="submit" class="btn"><?php echo $contact ? 'Guardar cambios' : 'Registrar contacto'; ?></button><?php if ($contact): ?><button type="submit" form="delete-contact" class="btn btn--danger">Eliminar contacto</button><?php endif; ?></div>
    </form>
    <?php if ($contact): ?><form id="delete-contact" method="post" action="<?php echo $baseUrl; ?>/admin/contactos/<?php echo (int) $contact->id; ?>/eliminar" hidden onsubmit="return confirm('¿Seguro que deseas eliminar este contacto?');"><input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>"></form><?php endif; ?>
    </div>
</section>
