<?php
require_once __DIR__ . '/config/database.php';

$idProducto = (int) ($_GET['id'] ?? 0);
$producto = db_one(
    'SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.stock, p.imagen, p.estado,
            c.nombre_categoria
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.id_producto = ?',
    [$idProducto]
);

if (!$producto) {
    flash('warning', 'Producto no encontrado.');
    redirect('index.php#productos');
}

$disponible = (int) $producto['estado'] === 1 && (int) $producto['stock'] > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($producto['nombre']); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container py-5">
    <?php include 'includes/flash.php'; ?>

    <div class="row g-5 align-items-start">
        <div class="col-lg-6">
            <img class="img-fluid rounded product-detail-image" src="<?php echo e(product_image($producto['imagen'])); ?>" alt="<?php echo e($producto['nombre']); ?>">
        </div>

        <div class="col-lg-6">
            <span class="eyebrow"><?php echo e($producto['nombre_categoria']); ?></span>
            <h1 class="fw-bold mb-3"><?php echo e($producto['nombre']); ?></h1>
            <p class="text-muted mb-2"><?php echo e($producto['marca']); ?></p>
            <h2 class="text-primary fw-bold mb-3"><?php echo money($producto['precio']); ?></h2>
            <p class="lead"><?php echo e($producto['descripcion'] ?: 'Producto gamer disponible en GameZone Store.'); ?></p>

            <div class="mb-4">
                <?php if ($disponible): ?>
                    <span class="badge text-bg-success">Stock disponible: <?php echo e((string) $producto['stock']); ?></span>
                <?php else: ?>
                    <span class="badge text-bg-danger">Sin stock disponible</span>
                <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="index.php#productos">Volver</a>

                <?php if (is_logged_in()): ?>
                    <form method="POST" action="favoritos.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="id_producto" value="<?php echo e((string) $producto['id_producto']); ?>">
                        <button class="btn btn-outline-primary" <?php echo !$disponible ? 'disabled' : ''; ?>>Favorito</button>
                    </form>
                    <form method="POST" action="carrito.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="id_producto" value="<?php echo e((string) $producto['id_producto']); ?>">
                        <button class="btn btn-success" <?php echo !$disponible ? 'disabled' : ''; ?>>Agregar al carrito</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-success" href="login.php">Iniciar sesion para comprar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
