<?php
require_once __DIR__ . '/config/database.php';

$busqueda = trim($_GET['q'] ?? '');
$categoriaFiltro = (int) ($_GET['categoria'] ?? 0);
$marcaFiltro = trim($_GET['marca'] ?? '');
$precioMax = (float) ($_GET['precio_max'] ?? 0);

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

include __DIR__ . '/includes/cards.php';
