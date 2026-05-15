<?php
require_once __DIR__ . '/config/database.php';

ensure_session();

if (empty($_SESSION['2fa_pending'])) {
    redirect('login.php');
}

$pending = $_SESSION['2fa_pending'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $codigo = trim($_POST['codigo'] ?? '');

        if (($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION['2fa_pending']);
            flash('danger', 'El codigo 2FA expiro. Inicia sesion nuevamente.');
            redirect('login.php');
        }

        $usuario = db_one(
            'SELECT id_usuario, nombre, rol, codigo_2fa, codigo_2fa_expira, intentos_2fa
             FROM usuario
             WHERE id_usuario = ?',
            [$pending['usuario_id']]
        );

        if (!$usuario || (int) $usuario['intentos_2fa'] >= 5) {
            unset($_SESSION['2fa_pending']);
            flash('danger', 'Demasiados intentos. Inicia sesion nuevamente.');
            redirect('login.php');
        }

        if (!$usuario['codigo_2fa_expira'] || strtotime($usuario['codigo_2fa_expira']) < time()) {
            unset($_SESSION['2fa_pending']);
            flash('danger', 'El codigo 2FA expiro. Inicia sesion nuevamente.');
            redirect('login.php');
        }

        if (hash_equals((string) $usuario['codigo_2fa'], $codigo)) {
            db()?->prepare('UPDATE usuario SET codigo_2fa = NULL, codigo_2fa_expira = NULL, intentos_2fa = 0 WHERE id_usuario = ?')->execute([$usuario['id_usuario']]);
            start_user_session($usuario);
            unset($_SESSION['2fa_pending']);

            flash('success', 'Verificacion completada.');
            redirect($usuario['rol'] === 'admin' ? 'dashboard.php' : 'index.php');
        }

        db()?->prepare('UPDATE usuario SET intentos_2fa = intentos_2fa + 1 WHERE id_usuario = ?')->execute([$usuario['id_usuario']]);
        $error = 'Codigo incorrecto.';
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
    <title>Verificacion 2FA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark">

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="card p-5 shadow-lg login-card">
        <h2 class="text-center mb-4">Verificacion 2FA</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php include 'includes/flash.php'; ?>

        <?php if (!empty($pending['dev_code'])): ?>
            <div class="alert alert-info">
                Codigo temporal de desarrollo: <strong><?php echo e($pending['dev_code']); ?></strong>
            </div>
        <?php endif; ?>

        <form method="POST" action="verificar_2fa.php" class="js-validate">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Codigo</label>
                <input type="text" name="codigo" class="form-control" maxlength="6" required>
            </div>

            <button class="btn btn-primary w-100">Verificar</button>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
