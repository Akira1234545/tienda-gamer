<?php
require_once __DIR__ . '/config/database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($nombre === '' || $correo === '' || $contrasena === '') {
            $error = 'Completa todos los campos.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresa un correo valido.';
        } elseif (strlen($contrasena) < 8) {
            $error = 'La contrasena debe tener al menos 8 caracteres.';
        } elseif (!db()) {
            $error = 'No se pudo conectar a la base de datos.';
        } else {
            $statement = db()->prepare('INSERT INTO usuario (nombre, correo, ' . column_password() . ', rol) VALUES (?, ?, ?, ?)');
            $statement->execute([$nombre, $correo, password_hash($contrasena, PASSWORD_DEFAULT), 'cliente']);
            $success = 'Cuenta creada correctamente. Ya puedes iniciar sesion.';
        }
    } catch (PDOException $exception) {
        $error = $exception->getCode() === '23000'
            ? 'Ese correo ya esta registrado.'
            : 'No se pudo crear la cuenta.';
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
    <title>Registro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4">
                <h2 class="text-center mb-4">Registro de Usuario</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo e($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php" class="js-validate">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contrasena</label>
                        <input type="password" name="contrasena" class="form-control" minlength="8" required>
                        <div class="form-text">Debe tener al menos 8 caracteres.</div>
                    </div>

                    <button class="btn btn-success w-100">Registrarse</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">Ya tengo cuenta</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
