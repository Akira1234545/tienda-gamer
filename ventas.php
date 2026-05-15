<?php
require_once __DIR__ . '/auth_admin.php';

$busquedaAdmin = trim($_GET['buscar'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $idVenta = (int) ($_POST['id_venta'] ?? 0);
        $estado = $_POST['estado_venta'] ?? 'Pendiente';
        $permitidos = ['Pendiente', 'Pagado', 'Entregado'];

        if ($idVenta > 0 && in_array($estado, $permitidos, true)) {
            db()?->prepare('UPDATE venta SET estado_venta = ? WHERE id_venta = ?')->execute([$estado, $idVenta]);
            flash('success', 'Estado de venta actualizado.');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect('ventas.php');
}

$whereVentas = [];
$paramsVentas = [];

if ($busquedaAdmin !== '') {
    $whereVentas[] = '(u.nombre LIKE ? OR u.correo LIKE ? OR v.estado_venta LIKE ? OR v.id_venta = ?)';
    $like = '%' . $busquedaAdmin . '%';
    $paramsVentas = [$like, $like, $like, (int) $busquedaAdmin];
}

$whereSqlVentas = $whereVentas ? ' WHERE ' . implode(' AND ', $whereVentas) : '';
$totalVentas = (int) db_value(
    'SELECT COUNT(*)
     FROM venta v
     INNER JOIN usuario u ON u.id_usuario = v.id_usuario' . $whereSqlVentas,
    $paramsVentas,
    0
);
$totalPaginas = max(1, (int) ceil($totalVentas / $porPagina));
$ventas = db_all(
    'SELECT v.id_venta, u.nombre AS cliente, v.fecha, v.total, v.estado_venta,
            COALESCE(SUM(d.cantidad), 0) AS total_items
     FROM venta v
     INNER JOIN usuario u ON u.id_usuario = v.id_usuario
     LEFT JOIN detalle_venta d ON d.id_venta = v.id_venta
     ' . $whereSqlVentas . "
     GROUP BY v.id_venta, u.nombre, v.fecha, v.total, v.estado_venta
     ORDER BY v.fecha DESC
     LIMIT $porPagina OFFSET $offset",
    $paramsVentas
);

$detallesPorVenta = [];

if ($ventas) {
    $ids = array_column($ventas, 'id_venta');
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
    <title>Ventas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>
        <h1 class="mb-4">Ventas</h1>

        <form method="GET" action="ventas.php" class="row g-2 mb-4">
            <div class="col-md-10">
                <input type="search" name="buscar" class="form-control" placeholder="Buscar por cliente, correo, estado o ID de venta" value="<?php echo e($busquedaAdmin); ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Actualizar</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$ventas): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($ventas as $venta): ?>
                        <?php
                        $badge = match ($venta['estado_venta']) {
                            'Pagado' => 'text-bg-success',
                            'Entregado' => 'text-bg-primary',
                            default => 'text-bg-warning',
                        };
                        ?>
                        <tr>
                            <td><?php echo e((string) $venta['id_venta']); ?></td>
                            <td><?php echo e($venta['cliente']); ?></td>
                            <td><?php echo e(date('d/m/Y H:i', strtotime($venta['fecha']))); ?></td>
                            <td><?php echo e((string) $venta['total_items']); ?></td>
                            <td><?php echo money($venta['total']); ?></td>
                            <td><span class="badge <?php echo $badge; ?>"><?php echo e($venta['estado_venta']); ?></span></td>
                            <td>
                                <form method="POST" action="ventas.php" class="d-flex gap-2">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_venta" value="<?php echo e((string) $venta['id_venta']); ?>">
                                    <select name="estado_venta" class="form-control form-control-sm">
                                        <?php foreach (['Pendiente', 'Pagado', 'Entregado'] as $estado): ?>
                                            <option value="<?php echo e($estado); ?>" <?php echo $estado === $venta['estado_venta'] ? 'selected' : ''; ?>>
                                                <?php echo e($estado); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary btn-sm">Guardar</button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#detalle-venta-<?php echo e((string) $venta['id_venta']); ?>">
                                    Ver productos
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="detalle-venta-<?php echo e((string) $venta['id_venta']); ?>">
                            <td colspan="8">
                                <div class="p-3 bg-light rounded">
                                    <h2 class="h6 fw-bold mb-3">Productos incluidos</h2>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Marca</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($detallesPorVenta[$venta['id_venta']] ?? [] as $detalle): ?>
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                            <a class="page-link" href="ventas.php?buscar=<?php echo urlencode($busquedaAdmin); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
