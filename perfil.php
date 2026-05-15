<?php
require_once __DIR__ . '/auth_cliente.php';

$usuario = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $estado2fa = isset($_POST['estado_2fa']) ? 1 : 0;
        $contrasenaActual = $_POST['contrasena_actual'] ?? '';
        $nuevaContrasena = $_POST['nueva_contrasena'] ?? '';
        $confirmarContrasena = $_POST['confirmar_contrasena'] ?? '';

        if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Nombre y correo valido son obligatorios.');
            redirect('perfil.php');
        }

        db()?->prepare('UPDATE usuario SET nombre = ?, correo = ?, estado_2fa = ? WHERE id_usuario = ?')
            ->execute([$nombre, $correo, $estado2fa, $_SESSION['usuario_id']]);

        if ($nuevaContrasena !== '' || $confirmarContrasena !== '' || $contrasenaActual !== '') {
            if (strlen($nuevaContrasena) < 8) {
                throw new RuntimeException('La nueva contrasena debe tener al menos 8 caracteres.');
            }

            if ($nuevaContrasena !== $confirmarContrasena) {
                throw new RuntimeException('La confirmacion de contrasena no coincide.');
            }

            $usuarioPassword = db_one(
                'SELECT ' . column_password() . ' AS password_hash FROM usuario WHERE id_usuario = ?',
                [$_SESSION['usuario_id']]
            );

            if (!$usuarioPassword || !password_verify($contrasenaActual, $usuarioPassword['password_hash'])) {
                throw new RuntimeException('La contrasena actual no es correcta.');
            }

            db()?->prepare('UPDATE usuario SET ' . column_password() . ' = ? WHERE id_usuario = ?')
                ->execute([password_hash($nuevaContrasena, PASSWORD_DEFAULT), $_SESSION['usuario_id']]);
        }

        $_SESSION['usuario_nombre'] = $nombre;
        flash('success', 'Perfil actualizado correctamente.');
    } catch (RuntimeException $exception) {
        flash('danger', $exception->getMessage());
    } catch (PDOException $exception) {
        flash('danger', $exception->getCode() === '23000' ? 'Ese correo ya esta en uso.' : 'No se pudo actualizar el perfil.');
    }

    redirect('perfil.php');
}

$usuario = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php if (is_admin()): ?>
    <div class="admin-layout d-flex">
        <?php include 'includes/sidebar.php'; ?>
<?php else: ?>
    <?php include 'includes/navbar.php'; ?>
    <div class="container py-5">
<?php endif; ?>

    <main class="<?php echo is_admin() ? 'container-fluid p-4' : ''; ?>">
        <?php include 'includes/flash.php'; ?>
        <h1 class="mb-4">Perfil</h1>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow p-4">
                    <form method="POST" action="perfil.php" class="js-validate">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo e($usuario['nombre']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" name="correo" class="form-control" value="<?php echo e($usuario['correo']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <input type="text" class="form-control" value="<?php echo e(ucfirst($usuario['rol'])); ?>" disabled>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="estado_2fa" name="estado_2fa" <?php echo (int) $usuario['estado_2fa'] === 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="estado_2fa">Activar verificacion 2FA</label>
                        </div>

                        <hr>
                        <h2 class="h5 fw-bold mb-3">Cambiar contrasena</h2>

                        <div class="mb-3">
                            <label class="form-label">Contrasena actual</label>
                            <input type="password" name="contrasena_actual" class="form-control" autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nueva contrasena</label>
                            <input type="password" name="nueva_contrasena" class="form-control" minlength="8" autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar nueva contrasena</label>
                            <input type="password" name="confirmar_contrasena" class="form-control" minlength="8" autocomplete="new-password">
                        </div>

                        <button class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    </div>

<?php if (!is_admin()): ?>
    <?php include 'includes/footer.php'; ?>
<?php endif; ?>

<script src="assets/js/app.js"></script>
</body>
</html>
