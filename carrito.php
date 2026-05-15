<?php
require_once __DIR__ . '/auth.php';

ensure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $accion = $_POST['accion'] ?? '';
        $idProducto = (int) ($_POST['id_producto'] ?? 0);

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if ($accion === 'agregar') {
            $producto = db_one('SELECT id_producto, stock FROM producto WHERE id_producto = ? AND estado = 1', [$idProducto]);

            if (!$producto) {
                throw new RuntimeException('Producto no disponible.');
            }

            $cantidadActual = $_SESSION['carrito'][$idProducto] ?? 0;

            if ($cantidadActual + 1 > (int) $producto['stock']) {
                throw new RuntimeException('No hay stock suficiente.');
            }

            $_SESSION['carrito'][$idProducto] = $cantidadActual + 1;
            flash('success', 'Producto agregado al carrito.');
            redirect('carrito.php');
        }

        if ($accion === 'actualizar') {
            $cantidades = $_POST['cantidades'] ?? [];

            foreach ($cantidades as $id => $cantidad) {
                $id = (int) $id;
                $cantidad = max(0, (int) $cantidad);
                $stock = (int) db_value('SELECT stock FROM producto WHERE id_producto = ?', [$id], 0);

                if ($cantidad === 0) {
                    unset($_SESSION['carrito'][$id]);
                    continue;
                }

                $_SESSION['carrito'][$id] = min($cantidad, $stock);
            }

            flash('success', 'Carrito actualizado.');
            redirect('carrito.php');
        }

        if ($accion === 'eliminar') {
            unset($_SESSION['carrito'][$idProducto]);
            flash('success', 'Producto eliminado del carrito.');
            redirect('carrito.php');
        }

        if ($accion === 'vaciar') {
            $_SESSION['carrito'] = [];
            flash('success', 'Carrito vaciado.');
            redirect('carrito.php');
        }

        if ($accion === 'comprar') {
            $items = cart_items();

            if (!$items) {
                throw new RuntimeException('El carrito esta vacio.');
            }

            $pdo = db();

            if (!$pdo) {
                throw new RuntimeException('No hay conexion con la base de datos.');
            }

            $pdo->beginTransaction();
            $total = 0;
            $detalle = [];

            foreach ($items as $id => $cantidad) {
                $producto = db_one('SELECT id_producto, nombre, precio, stock FROM producto WHERE id_producto = ? AND estado = 1', [(int) $id]);

                if (!$producto || (int) $producto['stock'] < (int) $cantidad) {
                    throw new RuntimeException('Stock insuficiente para completar la compra.');
                }

                $subtotal = (float) $producto['precio'] * (int) $cantidad;
                $total += $subtotal;
                $detalle[] = [$producto, (int) $cantidad, $subtotal];
            }

            $pdo->prepare('INSERT INTO venta (id_usuario, total, estado_venta) VALUES (?, ?, ?)')->execute([$_SESSION['usuario_id'], $total, 'Pagado']);
            $idVenta = (int) $pdo->lastInsertId();

            foreach ($detalle as [$producto, $cantidad, $subtotal]) {
                $pdo->prepare('INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)')
                    ->execute([$idVenta, $producto['id_producto'], $cantidad, $subtotal]);
                $pdo->prepare('UPDATE producto SET stock = stock - ? WHERE id_producto = ?')
                    ->execute([$cantidad, $producto['id_producto']]);
            }

            $pdo->commit();
            $_SESSION['carrito'] = [];
            flash('success', 'Compra confirmada correctamente.');
            redirect('compras.php');
        }
    } catch (Throwable $exception) {
        if (db()?->inTransaction()) {
            db()?->rollBack();
        }

        flash('danger', $exception->getMessage());
        redirect('carrito.php');
    }
}

$items = cart_items();
$productosCarrito = [];
$total = 0;

if ($items) {
    $ids = array_keys($items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $productosCarrito = db_all("SELECT id_producto, nombre, precio, stock FROM producto WHERE id_producto IN ($placeholders)", $ids);

    foreach ($productosCarrito as $producto) {
        $cantidad = (int) ($items[$producto['id_producto']] ?? 0);
        $total += (float) $producto['precio'] * $cantidad;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <?php include 'includes/flash.php'; ?>
    <h2 class="mb-4">Carrito de Compras</h2>

    <form method="POST" action="carrito.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="accion" value="actualizar">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$productosCarrito): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Tu carrito esta vacio.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($productosCarrito as $producto): ?>
                        <?php
                        $cantidad = (int) ($items[$producto['id_producto']] ?? 0);
                        $subtotal = (float) $producto['precio'] * $cantidad;
                        ?>
                        <tr>
                            <td><?php echo e($producto['nombre']); ?></td>
                            <td>
                                <input type="number" name="cantidades[<?php echo e((string) $producto['id_producto']); ?>]" value="<?php echo e((string) $cantidad); ?>" min="0" max="<?php echo e((string) $producto['stock']); ?>" class="form-control cart-quantity">
                                <small class="text-muted">Stock: <?php echo e((string) $producto['stock']); ?></small>
                            </td>
                            <td><?php echo money($producto['precio']); ?></td>
                            <td><?php echo money($subtotal); ?></td>
                            <td>
                                <button class="btn btn-danger" type="submit" form="remove-<?php echo e((string) $producto['id_producto']); ?>">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 gap-3">
            <div class="d-flex gap-2">
                <button class="btn btn-primary" <?php echo !$productosCarrito ? 'disabled' : ''; ?>>Actualizar</button>
                <button class="btn btn-outline-danger" type="submit" form="empty-cart" <?php echo !$productosCarrito ? 'disabled' : ''; ?>>Vaciar</button>
            </div>

            <div class="text-end">
                <h3>Total: <?php echo money($total); ?></h3>
                <button class="btn btn-success" type="submit" form="checkout" <?php echo !$productosCarrito ? 'disabled' : ''; ?>>Confirmar Compra</button>
            </div>
        </div>
    </form>

    <?php foreach ($productosCarrito as $producto): ?>
        <form id="remove-<?php echo e((string) $producto['id_producto']); ?>" method="POST" action="carrito.php" class="d-none">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_producto" value="<?php echo e((string) $producto['id_producto']); ?>">
        </form>
    <?php endforeach; ?>

    <form id="empty-cart" method="POST" action="carrito.php" class="d-none">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="accion" value="vaciar">
    </form>

    <form id="checkout" method="POST" action="carrito.php" class="d-none">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="accion" value="comprar">
    </form>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
