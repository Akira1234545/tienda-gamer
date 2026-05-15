<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($productosDestacados)) {
    $productosDestacados = db_all(
        'SELECT id_producto, nombre, descripcion, precio, stock, imagen
         FROM producto
         WHERE estado = 1 AND stock > 0
         ORDER BY id_producto DESC
         LIMIT 3'
    );
}

if (!$productosDestacados && !db()) {
    $productosDestacados = [
        [
            'id_producto' => 1,
            'nombre' => 'Laptop Gamer ASUS',
            'descripcion' => 'RTX 4060 - Ryzen 7 - 16GB RAM',
            'precio' => 1200,
            'imagen' => 'producto1.jpg',
        ],
        [
            'id_producto' => 2,
            'nombre' => 'Teclado Mecanico RGB',
            'descripcion' => 'Switch Blue - RGB completo',
            'precio' => 90,
            'imagen' => 'producto2.jpg',
        ],
        [
            'id_producto' => 3,
            'nombre' => 'Monitor 240Hz',
            'descripcion' => 'Full HD - 1ms - Gamer Pro',
            'precio' => 450,
            'imagen' => 'producto3.jpg',
        ],
    ];
}
?>

<?php if (!$productosDestacados): ?>
    <div class="col-12">
        <div class="alert alert-light border text-center mb-0">
            Aun no hay productos activos en la base de datos.
        </div>
    </div>
<?php endif; ?>

<?php foreach ($productosDestacados as $index => $producto): ?>
    <div class="col-md-4">
        <div class="card product-card h-100">
            <img src="<?php echo e(product_image($producto['imagen'] ?? null, 'producto' . ($index + 1) . '.jpg')); ?>" class="card-img-top" alt="<?php echo e($producto['nombre']); ?>">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">
                    <a class="text-decoration-none text-dark" href="producto.php?id=<?php echo e((string) ($producto['id_producto'] ?? 0)); ?>">
                        <?php echo e($producto['nombre']); ?>
                    </a>
                </h5>
                <p class="card-text"><?php echo e($producto['descripcion'] ?? 'Producto gamer disponible'); ?></p>
                <div class="product-footer mt-auto">
                    <div class="product-meta">
                        <h4><?php echo money($producto['precio']); ?></h4>
                        <?php if (isset($producto['stock'])): ?>
                            <p class="text-muted small mb-0">Stock: <?php echo e((string) $producto['stock']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions">
                        <a class="btn btn-outline-secondary" href="producto.php?id=<?php echo e((string) ($producto['id_producto'] ?? 0)); ?>">Ver</a>
                        <?php if (is_logged_in()): ?>
                            <form method="POST" action="favoritos.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="agregar">
                                <input type="hidden" name="id_producto" value="<?php echo e((string) ($producto['id_producto'] ?? 0)); ?>">
                                <button class="btn btn-outline-primary">Favorito</button>
                            </form>
                            <form method="POST" action="carrito.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="agregar">
                                <input type="hidden" name="id_producto" value="<?php echo e((string) ($producto['id_producto'] ?? 0)); ?>">
                                <button class="btn btn-success">Agregar</button>
                            </form>
                        <?php else: ?>
                            <a class="btn btn-success" href="login.php">Agregar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
