<?php
require_once __DIR__ . '/auth.php';

$compras = db_all(
    'SELECT id_venta, fecha, total, estado_venta
     FROM venta
     WHERE id_usuario = ?
     ORDER BY fecha DESC',
    [$_SESSION['usuario_id']]
);

$detallesPorVenta = [];

if ($compras) {
    $ids = array_column($compras, 'id_venta');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $detalles = db_all(
        "SELECT d.id_venta, p.nombre, p.marca, d.cantidad, d.subtotal
         FROM detalle_venta d
         INNER JOIN producto p ON p.id_producto = d.id_producto
         WHERE d.id_venta IN ($placeholders)
         ORDER BY d.id_detalle ASC",
        $ids
    );

    foreach ($detalles as $detalle) {
        $detallesPorVenta[$detalle['id_venta']][] = $detalle;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis compras</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container py-5">
    <?php include 'includes/flash.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="eyebrow">Historial</span>
            <h1 class="fw-bold mb-0">Mis compras</h1>
        </div>
        <a class="btn btn-outline-primary" href="index.php#productos">Seguir comprando</a>
    </div>

    <?php if (!$compras): ?>
        <div class="alert alert-light border text-center">Todavia no tienes compras registradas.</div>
    <?php endif; ?>

    <div class="accordion" id="comprasAccordion">
        <?php foreach ($compras as $index => $compra): ?>
            <?php
            $collapseId = 'compra-' . (int) $compra['id_venta'];
            $badge = match ($compra['estado_venta']) {
                'Pagado' => 'text-bg-success',
                'Entregado' => 'text-bg-primary',
                default => 'text-bg-warning',
            };
            ?>
            <div class="accordion-item card mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($collapseId); ?>">
                        Compra #<?php echo e((string) $compra['id_venta']); ?> -
                        <?php echo e(date('d/m/Y H:i', strtotime($compra['fecha']))); ?> -
                        <?php echo money($compra['total']); ?>
                    </button>
                </h2>
                <div id="<?php echo e($collapseId); ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#comprasAccordion">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge <?php echo $badge; ?>"><?php echo e($compra['estado_venta']); ?></span>
                            <strong>Total: <?php echo money($compra['total']); ?></strong>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detallesPorVenta[$compra['id_venta']] ?? [] as $detalle): ?>
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
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
