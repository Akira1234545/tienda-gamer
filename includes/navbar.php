<?php
require_once __DIR__ . '/../config/database.php';
ensure_session();
?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <span class="brand-mark">GZ</span>
            GameZone
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="index.php#productos">Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#categorias">Categorias</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#tienda">Tienda</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#beneficios">Beneficios</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="carrito.php">
                            Carrito
                            <?php if (cart_count() > 0): ?>
                                <span class="badge text-bg-danger ms-1"><?php echo e((string) cart_count()); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="favoritos.php">Favoritos</a></li>
                    <li class="nav-item"><a class="nav-link" href="compras.php">Mis compras</a></li>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Admin</a></li>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="perfil.php"><?php echo e($_SESSION['usuario_nombre'] ?? 'Perfil'); ?></a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-lg-2 mt-2 mt-lg-0" href="logout.php">Salir</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-lg-2 mt-2 mt-lg-0" href="register.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
