<section class="section">
    <h1>Administrar productos</h1>
    <a href="/admin/products.php?action=new">Nuevo producto</a>
    <?php if (!$products): ?>
        <p>No hay productos registrados.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Nombre</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a href="/admin/products.php?action=edit&id=<?php echo $product->id; ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
