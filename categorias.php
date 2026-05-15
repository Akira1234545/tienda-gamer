<?php
require_once __DIR__ . '/auth_admin.php';

$categoriaEditar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $accion = $_POST['accion'] ?? '';
        $id = (int) ($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre_categoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($accion === 'crear' || $accion === 'actualizar') {
            if ($nombre === '') {
                throw new RuntimeException('El nombre de la categoria es obligatorio.');
            }

            if ($accion === 'crear') {
                db()?->prepare('INSERT INTO categoria (nombre_categoria, descripcion) VALUES (?, ?)')->execute([$nombre, $descripcion]);
                flash('success', 'Categoria creada correctamente.');
            } else {
                db()?->prepare('UPDATE categoria SET nombre_categoria = ?, descripcion = ? WHERE id_categoria = ?')->execute([$nombre, $descripcion, $id]);
                flash('success', 'Categoria actualizada correctamente.');
            }

            redirect('categorias.php');
        }

        if ($accion === 'eliminar') {
            $totalProductos = (int) db_value('SELECT COUNT(*) FROM producto WHERE id_categoria = ?', [$id], 0);

            if ($totalProductos > 0) {
                throw new RuntimeException('No puedes eliminar una categoria con productos asignados.');
            }

            db()?->prepare('DELETE FROM categoria WHERE id_categoria = ?')->execute([$id]);
            flash('success', 'Categoria eliminada correctamente.');
            redirect('categorias.php');
        }
    } catch (Throwable $exception) {
        flash('danger', 'No se pudo completar la accion. ' . $exception->getMessage());
        redirect('categorias.php');
    }
}

if (isset($_GET['editar'])) {
    $categoriaEditar = db_one('SELECT * FROM categoria WHERE id_categoria = ?', [(int) $_GET['editar']]);
}

$categorias = db_all(
    'SELECT c.id_categoria, c.nombre_categoria, c.descripcion, COUNT(p.id_producto) AS total_productos
     FROM categoria c
     LEFT JOIN producto p ON p.id_categoria = c.id_categoria
     GROUP BY c.id_categoria, c.nombre_categoria, c.descripcion
     ORDER BY c.nombre_categoria'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Categorias</h1>
            <a class="btn btn-outline-primary" href="categorias.php">Limpiar formulario</a>
        </div>

        <div class="card p-4 mb-4">
            <h2 class="h4 fw-bold mb-3"><?php echo $categoriaEditar ? 'Editar categoria' : 'Nueva categoria'; ?></h2>
            <form method="POST" action="categorias.php" class="row g-3 js-validate">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion" value="<?php echo $categoriaEditar ? 'actualizar' : 'crear'; ?>">
                <input type="hidden" name="id_categoria" value="<?php echo e((string) ($categoriaEditar['id_categoria'] ?? '')); ?>">

                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre_categoria" class="form-control" value="<?php echo e($categoriaEditar['nombre_categoria'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descripcion</label>
                    <input type="text" name="descripcion" class="form-control" value="<?php echo e($categoriaEditar['descripcion'] ?? ''); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?php echo $categoriaEditar ? 'Actualizar' : 'Crear'; ?></button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <?php if (!$categorias): ?>
                <div class="col-12">
                    <div class="alert alert-light border text-center mb-0">No hay categorias registradas.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($categorias as $categoria): ?>
                <div class="col-md-3">
                    <div class="card shadow p-4 h-100">
                        <h4><?php echo e($categoria['nombre_categoria']); ?></h4>
                        <p class="text-muted"><?php echo e($categoria['descripcion'] ?? 'Sin descripcion'); ?></p>
                        <p class="text-muted"><?php echo e((string) $categoria['total_productos']); ?> productos</p>
                        <div class="d-flex gap-2 mt-auto">
                            <a class="btn btn-warning btn-sm" href="categorias.php?editar=<?php echo e((string) $categoria['id_categoria']); ?>">Editar</a>
                            <form method="POST" action="categorias.php" class="js-confirm" data-confirm="Eliminar categoria?">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_categoria" value="<?php echo e((string) $categoria['id_categoria']); ?>">
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
