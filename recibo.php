<?php
require_once __DIR__ . '/auth_cliente.php';

$idVenta = (int) ($_GET['id'] ?? 0);
$venta = db_one(
    'SELECT v.id_venta, v.fecha, v.total, v.estado_venta, u.nombre, u.correo
     FROM venta v
     INNER JOIN usuario u ON u.id_usuario = v.id_usuario
     WHERE v.id_venta = ? AND v.id_usuario = ?',
    [$idVenta, $_SESSION['usuario_id']]
);

if (!$venta) {
    flash('warning', 'No se encontro el recibo solicitado.');
    redirect('compras.php');
}

$detalles = db_all(
    'SELECT p.nombre, p.marca, d.cantidad, d.subtotal
     FROM detalle_venta d
     INNER JOIN producto p ON p.id_producto = d.id_producto
     WHERE d.id_venta = ?
     ORDER BY d.id_detalle ASC',
    [$idVenta]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de compra</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container py-5">
    <?php include 'includes/flash.php'; ?>

    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <span class="eyebrow">Compra registrada</span>
                <h1 class="fw-bold mb-2">Recibo #<?php echo e((string) $venta['id_venta']); ?></h1>
                <p class="text-muted mb-0"><?php echo e(date('d/m/Y H:i', strtotime($venta['fecha']))); ?></p>
            </div>
            <span class="badge text-bg-warning fs-6"><?php echo e($venta['estado_venta']); ?></span>
        </div>

        <hr>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <strong>Cliente</strong>
                <p class="mb-0"><?php echo e($venta['nombre']); ?></p>
            </div>
            <div class="col-md-6">
                <strong>Correo</strong>
                <p class="mb-0"><?php echo e($venta['correo']); ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td><?php echo e($detalle['nombre']); ?></td>
                            <td><?php echo e($detalle['marca']); ?></td>
                            <td><?php echo e((string) $detalle['cantidad']); ?></td>
                            <td><?php echo money($detalle['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <p class="text-muted mb-0">Tu pedido quedo pendiente para revision administrativa.</p>
            <h2 class="h3 mb-0">Total: <?php echo money($venta['total']); ?></h2>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="compras.php">Ver mis compras</a>
        <a class="btn btn-outline-primary" href="index.php#productos">Seguir comprando</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
