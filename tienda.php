<?php
require_once __DIR__ . '/auth_admin.php';

function ensure_store_table(): void
{
    db()?->exec(
        'CREATE TABLE IF NOT EXISTS configuracion_tienda (
            id_configuracion INT PRIMARY KEY DEFAULT 1,
            nombre VARCHAR(120) NOT NULL,
            descripcion TEXT NOT NULL,
            ubicacion VARCHAR(180) NOT NULL,
            horario VARCHAR(120) NOT NULL,
            correo VARCHAR(150) NOT NULL,
            telefono VARCHAR(50) NOT NULL
        )'
    );
}

ensure_store_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $horario = trim($_POST['horario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if ($nombre === '' || $descripcion === '' || $ubicacion === '' || $horario === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $telefono === '') {
            throw new RuntimeException('Completa correctamente la informacion de la tienda.');
        }

        db()?->prepare(
            'INSERT INTO configuracion_tienda (id_configuracion, nombre, descripcion, ubicacion, horario, correo, telefono)
             VALUES (1, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                descripcion = VALUES(descripcion),
                ubicacion = VALUES(ubicacion),
                horario = VALUES(horario),
                correo = VALUES(correo),
                telefono = VALUES(telefono)'
        )->execute([$nombre, $descripcion, $ubicacion, $horario, $correo, $telefono]);

        flash('success', 'Informacion de la tienda actualizada.');
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect('tienda.php');
}

$infoTienda = store_info();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informacion de tienda</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>
        <h1 class="mb-4">Informacion de la tienda</h1>

        <div class="card p-4">
            <form method="POST" action="tienda.php" class="row g-3 js-validate">
                <?php echo csrf_field(); ?>

                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo e($infoTienda['nombre']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" value="<?php echo e($infoTienda['correo']); ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" rows="3" class="form-control" required><?php echo e($infoTienda['descripcion']); ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ubicacion</label>
                    <input type="text" name="ubicacion" class="form-control" value="<?php echo e($infoTienda['ubicacion']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Horario</label>
                    <input type="text" name="horario" class="form-control" value="<?php echo e($infoTienda['horario']); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" class="form-control" value="<?php echo e($infoTienda['telefono']); ?>" required>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Guardar informacion</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
