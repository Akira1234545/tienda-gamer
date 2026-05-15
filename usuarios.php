<?php
require_once __DIR__ . '/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $rol = $_POST['rol'] ?? 'cliente';

        if ($idUsuario > 0 && in_array($rol, ['admin', 'cliente'], true)) {
            db()?->prepare('UPDATE usuario SET rol = ? WHERE id_usuario = ?')->execute([$rol, $idUsuario]);
            flash('success', 'Rol actualizado correctamente.');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect('usuarios.php');
}

$usuarios = db_all(
    'SELECT id_usuario, nombre, correo, rol, estado_2fa, fecha_registro
     FROM usuario
     ORDER BY id_usuario DESC'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="admin-layout d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="container-fluid p-4">
        <?php include 'includes/flash.php'; ?>
        <h1 class="mb-4">Usuarios</h1>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>2FA</th>
                        <th>Registro</th>
                        <th>Actualizar rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$usuarios): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo e((string) $usuario['id_usuario']); ?></td>
                            <td><?php echo e($usuario['nombre']); ?></td>
                            <td><?php echo e($usuario['correo']); ?></td>
                            <td><?php echo e(ucfirst($usuario['rol'])); ?></td>
                            <td>
                                <span class="badge <?php echo (int) $usuario['estado_2fa'] === 1 ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                    <?php echo (int) $usuario['estado_2fa'] === 1 ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td><?php echo e(date('d/m/Y', strtotime($usuario['fecha_registro']))); ?></td>
                            <td>
                                <form method="POST" action="usuarios.php" class="d-flex gap-2">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_usuario" value="<?php echo e((string) $usuario['id_usuario']); ?>">
                                    <select name="rol" class="form-control form-control-sm">
                                        <option value="cliente" <?php echo $usuario['rol'] === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                                        <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
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
