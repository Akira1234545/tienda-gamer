<?php
require_once __DIR__ . '/auth_admin.php';

$categorias = db_all('SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria');
$productoEditar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear' || $accion === 'actualizar') {
            $id = (int) ($_POST['id_producto'] ?? 0);
            $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $marca = trim($_POST['marca'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float) ($_POST['precio'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $estado = isset($_POST['estado']) ? 1 : 0;

            if ($idCategoria <= 0 || $nombre === '' || $marca === '' || $precio <= 0 || $stock < 0) {
                throw new RuntimeException('Completa los datos del producto correctamente.');
            }

            if ($stock === 0) {
                $estado = 0;
            }

            $imagen = upload_product_image($_FILES['imagen'] ?? []);

            if ($accion === 'crear') {
                $sql = 'INSERT INTO producto (id_categoria, nombre, marca, descripcion, precio, stock, imagen, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                db()?->prepare($sql)->execute([$idCategoria, $nombre, $marca, $descripcion, $precio, $stock, $imagen, $estado]);
                flash('success', 'Producto creado correctamente.');
            } else {
                if ($id <= 0) {
                    throw new RuntimeException('Producto invalido.');
                }

                if ($imagen) {
                    $sql = 'UPDATE producto
                            SET id_categoria = ?, nombre = ?, marca = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, estado = ?
                            WHERE id_producto = ?';
                    db()?->prepare($sql)->execute([$idCategoria, $nombre, $marca, $descripcion, $precio, $stock, $imagen, $estado, $id]);
                } else {
                    $sql = 'UPDATE producto
                            SET id_categoria = ?, nombre = ?, marca = ?, descripcion = ?, precio = ?, stock = ?, estado = ?
                            WHERE id_producto = ?';
                    db()?->prepare($sql)->execute([$idCategoria, $nombre, $marca, $descripcion, $precio, $stock, $estado, $id]);
                }

                flash('success', 'Producto actualizado correctamente.');
            }

            redirect('productos.php');
        }

        if ($accion === 'eliminar') {
            $id = (int) ($_POST['id_producto'] ?? 0);
            db()?->prepare('DELETE FROM producto WHERE id_producto = ?')->execute([$id]);
            flash('success', 'Producto eliminado correctamente.');
            redirect('productos.php');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
        redirect('productos.php');
    }
}

if (isset($_GET['editar'])) {
    $productoEditar = db_one('SELECT * FROM producto WHERE id_producto = ?', [(int) $_GET['editar']]);
}

$productos = db_all(
    'SELECT p.id_producto, p.nombre, p.marca, p.precio, p.stock, p.estado, p.imagen, c.nombre_categoria
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria
     ORDER BY p.id_producto DESC'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Productos</h1>
            <a class="btn btn-outline-primary" href="productos.php">Limpiar formulario</a>
        </div>

        <div class="card p-4 mb-4">
            <h2 class="h4 fw-bold mb-3"><?php echo $productoEditar ? 'Editar producto' : 'Nuevo producto'; ?></h2>
            <form method="POST" action="productos.php" enctype="multipart/form-data" class="row g-3 js-validate">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion" value="<?php echo $productoEditar ? 'actualizar' : 'crear'; ?>">
                <input type="hidden" name="id_producto" value="<?php echo e((string) ($productoEditar['id_producto'] ?? '')); ?>">

                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo e($productoEditar['nombre'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control" value="<?php echo e($productoEditar['marca'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select name="id_categoria" class="form-control" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo e((string) $categoria['id_categoria']); ?>" <?php echo (int) ($productoEditar['id_categoria'] ?? 0) === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo e($categoria['nombre_categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="estado" id="estado" <?php echo (int) ($productoEditar['estado'] ?? 1) === 1 ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="estado">Activo</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Precio</label>
                    <input type="number" name="precio" min="0.01" step="0.01" class="form-control" value="<?php echo e((string) ($productoEditar['precio'] ?? '')); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" min="0" class="form-control" value="<?php echo e((string) ($productoEditar['stock'] ?? '')); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagen</label>
                    <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-12">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" rows="3" class="form-control"><?php echo e($productoEditar['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><?php echo $productoEditar ? 'Guardar cambios' : 'Crear producto'; ?></button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Categoria</th>
                        <th>Marca</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$productos): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No hay productos registrados.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo e((string) $producto['id_producto']); ?></td>
                            <td><img class="table-thumb" src="<?php echo e(product_image($producto['imagen'])); ?>" alt="<?php echo e($producto['nombre']); ?>"></td>
                            <td><?php echo e($producto['nombre']); ?></td>
                            <td><?php echo e($producto['nombre_categoria']); ?></td>
                            <td><?php echo e($producto['marca']); ?></td>
                            <td><?php echo money($producto['precio']); ?></td>
                            <td>
                                <span class="badge <?php echo (int) $producto['stock'] <= 0 ? 'text-bg-danger' : ((int) $producto['stock'] <= 5 ? 'text-bg-warning' : 'text-bg-success'); ?>">
                                    <?php echo e((string) $producto['stock']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo (int) $producto['estado'] === 1 ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                    <?php echo (int) $producto['estado'] === 1 ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a class="btn btn-warning btn-sm" href="productos.php?editar=<?php echo e((string) $producto['id_producto']); ?>">Editar</a>
                                <form method="POST" action="productos.php" class="d-inline js-confirm" data-confirm="Eliminar producto?">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_producto" value="<?php echo e((string) $producto['id_producto']); ?>">
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
