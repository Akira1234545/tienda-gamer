<?php
require_once __DIR__ . '/auth_admin.php';

$totalVentas = db_value('SELECT COUNT(*) FROM venta', [], 0);
$totalProductos = db_value('SELECT COUNT(*) FROM producto', [], 0);
$totalUsuarios = db_value('SELECT COUNT(*) FROM usuario', [], 0);
$totalIngresos = db_value('SELECT COALESCE(SUM(total), 0) FROM venta WHERE estado_venta IN ("Pagado", "Entregado")', [], 0);
$ventasRecientes = db_all(
    'SELECT v.id_venta, u.nombre AS cliente, v.fecha, v.total, v.estado_venta
     FROM venta v
     INNER JOIN usuario u ON u.id_usuario = v.id_usuario
     ORDER BY v.fecha DESC
     LIMIT 5'
);
$productosBajoStock = db_all(
    'SELECT nombre, stock
     FROM producto
     WHERE stock <= 5
     ORDER BY stock ASC
     LIMIT 6'
);
$productosMasVendidos = db_all(
    'SELECT p.nombre, COALESCE(SUM(d.cantidad), 0) AS vendidos
     FROM detalle_venta d
     INNER JOIN producto p ON p.id_producto = d.id_producto
     GROUP BY p.id_producto, p.nombre
     ORDER BY vendidos DESC
     LIMIT 6'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>
        <h1 class="mb-4">Dashboard Administrativo</h1>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card stat-card shadow p-4 text-center">
                    <h2><?php echo e((string) $totalVentas); ?></h2>
                    <p class="mb-0">Ventas Totales</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow p-4 text-center">
                    <h2><?php echo e((string) $totalProductos); ?></h2>
                    <p class="mb-0">Productos</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow p-4 text-center">
                    <h2><?php echo e((string) $totalUsuarios); ?></h2>
                    <p class="mb-0">Usuarios</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow p-4 text-center">
                    <h2><?php echo money($totalIngresos); ?></h2>
                    <p class="mb-0">Ingresos</p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <h2 class="h4 fw-bold mb-3">Ventas recientes</h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$ventasRecientes): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay ventas registradas.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($ventasRecientes as $venta): ?>
                                    <tr>
                                        <td><?php echo e((string) $venta['id_venta']); ?></td>
                                        <td><?php echo e($venta['cliente']); ?></td>
                                        <td><?php echo e(date('d/m/Y H:i', strtotime($venta['fecha']))); ?></td>
                                        <td><?php echo money($venta['total']); ?></td>
                                        <td><span class="badge text-bg-secondary"><?php echo e($venta['estado_venta']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h2 class="h4 fw-bold mb-3">Bajo stock</h2>
                    <div class="card p-4">
                        <?php if (!$productosBajoStock): ?>
                            <p class="text-muted mb-0">No hay productos en bajo stock.</p>
                        <?php else: ?>
                            <canvas id="stockChart" height="260"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-12">
                    <h2 class="h4 fw-bold mb-3">Productos mas vendidos</h2>
                    <div class="card p-4">
                        <?php if (!$productosMasVendidos): ?>
                            <p class="text-muted mb-0">Aun no hay productos vendidos.</p>
                        <?php else: ?>
                            <canvas id="soldChart" height="110"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const stockLabels = <?php echo json_encode(array_column($productosBajoStock, 'nombre')); ?>;
const stockData = <?php echo json_encode(array_map('intval', array_column($productosBajoStock, 'stock'))); ?>;
const soldLabels = <?php echo json_encode(array_column($productosMasVendidos, 'nombre')); ?>;
const soldData = <?php echo json_encode(array_map('intval', array_column($productosMasVendidos, 'vendidos'))); ?>;

if (document.getElementById('stockChart')) {
    new Chart(document.getElementById('stockChart'), {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [{
                label: 'Stock',
                data: stockData,
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

if (document.getElementById('soldChart')) {
    new Chart(document.getElementById('soldChart'), {
        type: 'bar',
        data: {
            labels: soldLabels,
            datasets: [{
                label: 'Unidades vendidas',
                data: soldData,
                backgroundColor: '#16a34a'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}
</script>
</body>
</html>
