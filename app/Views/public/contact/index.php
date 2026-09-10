<?php $company = require dirname(__DIR__, 4) . '/config/company.php'; ?>
<main>
    <section class="section section--gray">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <h1 class="contact-info__title">Contáctanos</h1>
                    <p class="contact-info__text">Estamos listos para ayudarte a elegir la mejor solución para tu empresa. Déjanos tus datos y un consultor te contactará en breve.</p>
                    <div class="contact-details">
                        <div class="contact-item"><div class="contact-item__icon"><i class="fas fa-phone"></i></div><div class="contact-item__content"><h3 class="contact-item__title">Teléfono</h3><p class="contact-item__text"><a href="tel:<?php echo htmlspecialchars($company['phone_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?></a></p></div></div>
                        <div class="contact-item"><div class="contact-item__icon"><i class="fas fa-envelope"></i></div><div class="contact-item__content"><h3 class="contact-item__title">Correo</h3><p class="contact-item__text"><a href="mailto:<?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?></a></p></div></div>
                        <div class="contact-item"><div class="contact-item__icon"><i class="fas fa-map-marker-alt"></i></div><div class="contact-item__content"><h3 class="contact-item__title">Ubicación</h3><p class="contact-item__text"><?php echo htmlspecialchars($company['location'], ENT_QUOTES, 'UTF-8'); ?></p></div></div>
                    </div>
                </div>
                <div class="contact-form"><div class="card form-card">
                    <?php if ($sent): ?>
                        <div class="alert alert--success"><i class="fas fa-check-circle alert__icon" aria-hidden="true"></i><h2>¡Mensaje enviado!</h2><p>Gracias por contactarnos. Un asesor se comunicará contigo muy pronto.</p><?php if ($ticketCreated): ?><p><strong>Ticket generado:</strong> #<?php echo (int) $ticketCreated->id; ?></p><?php endif; ?><a href="<?php echo Url::to('/'); ?>" class="btn alert__action">Volver al inicio</a></div>
                    <?php else: ?>
                        <?php if ($error): ?><div class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <?php if ($context): ?><div class="alert alert--info"><strong>Consulta relacionada:</strong> <?php echo htmlspecialchars($context, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <form method="post" enctype="multipart/form-data" class="form">
                            <?php echo $csrf; ?><input type="hidden" name="product_id" value="<?php echo (int) $productId; ?>"><input type="hidden" name="promo_id" value="<?php echo (int) $promoId; ?>">
                            <div class="form__group"><label for="nombre" class="form__label">Nombre completo *</label><input type="text" id="nombre" name="nombre" class="form__input" required value="<?php echo htmlspecialchars($values['nombre'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                            <div class="form__row"><div class="form__group"><label for="email" class="form__label">Correo electrónico</label><input type="email" id="email" name="email" class="form__input" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'); ?>"></div><div class="form__group"><label for="telefono" class="form__label">Teléfono *</label><input type="tel" id="telefono" name="telefono" class="form__input" required value="<?php echo htmlspecialchars($values['telefono'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                            <div class="form__group"><label for="opcion" class="form__label">Tipo de consulta *</label><select id="opcion" name="opcion" class="form__select" required><option value="">-- Seleccione --</option><?php foreach (($consultationTypes ?? []) as $value => $label): ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $values['opcion'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                            <div class="form__group"><label for="mensaje" class="form__label">Mensaje *</label><textarea id="mensaje" name="mensaje" class="form__textarea" rows="5" required><?php echo htmlspecialchars($values['mensaje'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                            <div class="form__group"><label for="imagen" class="form__label">Adjuntar imagen (opcional)</label><input type="file" id="imagen" name="imagen" class="form__input" accept="image/*"></div>
                            <button type="submit" class="btn">Enviar mensaje</button>
                        </form>
                    <?php endif; ?>
                </div></div>
            </div>
        </div>
    </section>
</main>
