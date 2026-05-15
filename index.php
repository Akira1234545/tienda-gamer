<?php
require_once __DIR__ . '/config/database.php';

$busqueda = trim($_GET['q'] ?? '');
$categoriaFiltro = (int) ($_GET['categoria'] ?? 0);
$marcaFiltro = trim($_GET['marca'] ?? '');
$precioMax = (float) ($_GET['precio_max'] ?? 0);
$infoTienda = store_info();

$categoriasLanding = db_all(
    'SELECT id_categoria, nombre_categoria
     FROM categoria
     ORDER BY id_categoria'
);

$marcas = db_all('SELECT DISTINCT marca FROM producto WHERE estado = 1 ORDER BY marca');

$where = ['p.estado = 1', 'p.stock > 0'];
$params = [];

if ($busqueda !== '') {
    $where[] = '(p.nombre LIKE ? OR p.marca LIKE ? OR c.nombre_categoria LIKE ?)';
    $like = '%' . $busqueda . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoriaFiltro > 0) {
    $where[] = 'p.id_categoria = ?';
    $params[] = $categoriaFiltro;
}

if ($marcaFiltro !== '') {
    $where[] = 'p.marca = ?';
    $params[] = $marcaFiltro;
}

if ($precioMax > 0) {
    $where[] = 'p.precio <= ?';
    $params[] = $precioMax;
}

$productosDestacados = db_all(
    'SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.imagen
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY p.id_producto DESC
     LIMIT 12',
    $params
);

if (!$categoriasLanding && !db()) {
    $categoriasLanding = [
        ['nombre_categoria' => 'Laptops'],
        ['nombre_categoria' => 'Teclados'],
        ['nombre_categoria' => 'Mouse'],
        ['nombre_categoria' => 'Monitores'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameZone Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="hero-section d-flex align-items-center text-white">
    <div class="container">
        <div class="hero-content">
            <span class="eyebrow">Hardware seleccionado para jugadores exigentes</span>
            <h1 class="display-3 fw-bold">Arma tu setup gamer</h1>
            <p class="lead">Componentes, perifericos y equipos listos para rendimiento competitivo.</p>
            <div class="hero-actions">
                <a href="#productos" class="btn btn-danger btn-lg">Ver Productos</a>
                <?php if (is_admin()): ?>
                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">Panel Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="container section-space" id="productos">
    <div class="section-heading text-center">
        <span class="eyebrow">Catalogo inicial</span>
        <h2>Productos destacados</h2>
        <p>Una seleccion visual para la primera version de la tienda.</p>
    </div>

    <form method="GET" action="index.php#productos" class="catalog-filter row g-3 mb-4" data-ajax-filter="search_productos.php" data-target="#productos-grid">
        <div class="col-md-4">
            <input type="search" name="q" class="form-control" placeholder="Buscar por producto, marca o categoria" value="<?php echo e($busqueda); ?>">
        </div>
        <div class="col-md-3">
            <select name="categoria" class="form-control">
                <option value="0">Todas las categorias</option>
                <?php foreach ($categoriasLanding as $categoria): ?>
                    <option value="<?php echo e((string) $categoria['id_categoria']); ?>" <?php echo $categoriaFiltro === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                        <?php echo e($categoria['nombre_categoria']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="marca" class="form-control">
                <option value="">Todas las marcas</option>
                <?php foreach ($marcas as $marca): ?>
                    <option value="<?php echo e($marca['marca']); ?>" <?php echo $marcaFiltro === $marca['marca'] ? 'selected' : ''; ?>>
                        <?php echo e($marca['marca']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="precio_max" class="form-control" min="0" step="0.01" placeholder="Precio max." value="<?php echo $precioMax > 0 ? e((string) $precioMax) : ''; ?>">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <div class="row g-4" id="productos-grid">
        <?php include 'includes/cards.php'; ?>
    </div>
</section>

<section class="category-section text-white section-space" id="categorias">
    <div class="container">
        <div class="section-heading text-center">
            <span class="eyebrow">Explora por tipo</span>
            <h2>Categorias</h2>
        </div>

        <div class="row text-center g-4">
            <?php if (!$categoriasLanding): ?>
                <div class="col-12">
                    <div class="alert alert-dark border text-center mb-0">Aun no hay categorias registradas.</div>
                </div>
            <?php endif; ?>

            <?php foreach (array_slice($categoriasLanding, 0, 4) as $categoria): ?>
                <div class="col-md-3">
                    <div class="category-box p-4 rounded">
                        <h4><?php echo e($categoria['nombre_categoria']); ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container section-space" id="tienda">
    <div class="section-heading text-center">
        <span class="eyebrow"><?php echo e($infoTienda['nombre']); ?></span>
        <h2>Informacion de la tienda</h2>
        <p><?php echo e($infoTienda['descripcion']); ?></p>
    </div>

    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="benefit-item p-4">
                <h4>Ubicacion</h4>
                <p><?php echo e($infoTienda['ubicacion']); ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="benefit-item p-4">
                <h4>Atencion</h4>
                <p><?php echo e($infoTienda['horario']); ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="benefit-item p-4">
                <h4>Contacto</h4>
                <p><?php echo e($infoTienda['correo']); ?> - <?php echo e($infoTienda['telefono']); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="container section-space" id="beneficios">
    <div class="section-heading text-center">
        <span class="eyebrow">Servicio</span>
        <h2>Por que elegirnos</h2>
    </div>

    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="benefit-item p-4">
                <span>01</span>
                <h4>Envios Rapidos</h4>
                <p>Despacho agil para que actualices tu setup sin esperas largas.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-item p-4">
                <span>02</span>
                <h4>Pagos Seguros</h4>
                <p>Flujo visual preparado para integrar metodos de pago confiables.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-item p-4">
                <span>03</span>
                <h4>Productos Originales</h4>
                <p>Catalogo enfocado en marcas reconocidas y equipos de alto desempeno.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
