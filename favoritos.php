<?php
require_once __DIR__ . '/auth_cliente.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $accion = $_POST['accion'] ?? '';
        $idProducto = (int) ($_POST['id_producto'] ?? 0);

        if ($accion === 'agregar') {
            $existe = db_one(
                'SELECT id_favorito FROM favorito WHERE id_usuario = ? AND id_producto = ?',
                [$_SESSION['usuario_id'], $idProducto]
            );

            if (!$existe) {
                db()?->prepare('INSERT INTO favorito (id_usuario, id_producto) VALUES (?, ?)')->execute([$_SESSION['usuario_id'], $idProducto]);
            }

            flash('success', 'Producto agregado a favoritos.');
            redirect('favoritos.php');
        }

        if ($accion === 'eliminar') {
            db()?->prepare('DELETE FROM favorito WHERE id_usuario = ? AND id_producto = ?')->execute([$_SESSION['usuario_id'], $idProducto]);
            flash('success', 'Favorito eliminado.');
            redirect('favoritos.php');
        }
    } catch (Throwable $exception) {
        flash('danger', 'No se pudo actualizar favoritos.');
        redirect('favoritos.php');
    }
}

$favoritos = db_all(
    'SELECT p.id_producto, p.nombre, p.precio, p.imagen, f.fecha
     FROM favorito f
     INNER JOIN producto p ON p.id_producto = f.id_producto
     WHERE f.id_usuario = ?
     ORDER BY f.fecha DESC',
    [$_SESSION['usuario_id']]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoritos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <?php include 'includes/flash.php'; ?>
    <h2 class="mb-4">Mis Favoritos</h2>

    <div class="row g-4">
        <?php if (!$favoritos): ?>
            <div class="col-12">
                <div class="alert alert-light border text-center mb-0">No hay productos favoritos para mostrar.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($favoritos as $favorito): ?>
            <div class="col-md-3">
                <div class="card shadow h-100">
                    <img src="<?php echo e(product_image($favorito['imagen'])); ?>" class="card-img-top" alt="<?php echo e($favorito['nombre']); ?>">

                    <div class="card-body text-center d-flex flex-column">
                        <h5><?php echo e($favorito['nombre']); ?></h5>
                        <p class="text-primary fw-bold"><?php echo money($favorito['precio']); ?></p>
                        <div class="d-flex gap-2 mt-auto">
                            <form method="POST" action="carrito.php" class="w-100">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="agregar">
                                <input type="hidden" name="id_producto" value="<?php echo e((string) $favorito['id_producto']); ?>">
                                <button class="btn btn-success w-100">Carrito</button>
                            </form>
                            <form method="POST" action="favoritos.php" class="w-100">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_producto" value="<?php echo e((string) $favorito['id_producto']); ?>">
                                <button class="btn btn-danger w-100">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
