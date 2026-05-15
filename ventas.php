<?php
require_once __DIR__ . '/auth_admin.php';

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

$ventas = db_all(
    'SELECT v.id_venta, u.nombre AS cliente, v.fecha, v.total, v.estado_venta,
            COALESCE(SUM(d.cantidad), 0) AS total_items
     FROM venta v
     INNER JOIN usuario u ON u.id_usuario = v.id_usuario
     LEFT JOIN detalle_venta d ON d.id_venta = v.id_venta
     GROUP BY v.id_venta, u.nombre, v.fecha, v.total, v.estado_venta
     ORDER BY v.fecha DESC'
);
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$ventas): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
