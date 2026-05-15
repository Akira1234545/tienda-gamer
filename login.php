<?php
require_once __DIR__ . '/config/database.php';

ensure_session();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $usuario = db_one(
            'SELECT id_usuario, nombre, correo, ' . column_password() . ' AS password_hash, rol, estado_2fa
             FROM usuario
             WHERE correo = ?',
            [$correo]
        );

        if (!$usuario) {
            $error = 'Usuario no encontrado.';
        } elseif (!password_verify($contrasena, $usuario['password_hash'])) {
            $error = 'Contrasena incorrecta.';
        } elseif ((int) $usuario['estado_2fa'] === 1) {
            $codigo = (string) random_int(100000, 999999);
            $expira = date('Y-m-d H:i:s', time() + 300);
            $statement = db()?->prepare('UPDATE usuario SET codigo_2fa = ?, codigo_2fa_expira = ?, intentos_2fa = 0 WHERE id_usuario = ?');
            $statement?->execute([$codigo, $expira, $usuario['id_usuario']]);

            $_SESSION['2fa_pending'] = [
                'usuario_id' => $usuario['id_usuario'],
                'usuario_nombre' => $usuario['nombre'],
                'usuario_rol' => $usuario['rol'],
                'correo' => $usuario['correo'],
                'expires_at' => time() + 300,
                'dev_code' => env('APP_DEBUG', 'false') === 'true' ? $codigo : null,
            ];

            $sent = send_2fa_email($usuario['correo'], $usuario['nombre'], $codigo);
            flash($sent ? 'success' : 'warning', $sent ? 'Codigo 2FA enviado a tu correo.' : 'SMTP no configurado. Usa el codigo de desarrollo mostrado.');
            redirect('verificar_2fa.php');
        } else {
            start_user_session($usuario);
            flash('success', 'Bienvenido, ' . $usuario['nombre'] . '.');
            redirect($usuario['rol'] === 'admin' ? 'dashboard.php' : 'index.php');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark">

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="card p-5 shadow-lg login-card">
        <h2 class="text-center mb-4">Iniciar Sesion</h2>

        <?php include 'includes/flash.php'; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (db_error() && !db()): ?>
            <div class="alert alert-warning">No se pudo conectar a la base de datos.</div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="js-validate">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Correo Electronico</label>
                <input type="email" name="correo" class="form-control" placeholder="usuario@correo.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contrasena</label>
                <input type="password" name="contrasena" class="form-control" placeholder="Ingresa tu contrasena" required>
            </div>

            <button class="btn btn-primary w-100">Ingresar</button>
        </form>

        <div class="text-center mt-3">
            <a href="register.php">Crear Cuenta</a>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
