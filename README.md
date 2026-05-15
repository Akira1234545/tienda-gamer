# GameZone Store

Primera version visual de un sistema web para venta de equipos gamer.

## Tecnologias

- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- PHP como estructura visual

## Paginas incluidas

- `index.php`: landing page
- `login.php`: inicio de sesion
- `register.php`: registro
- `dashboard.php`: panel administrativo
- `productos.php`: administracion de productos
- `carrito.php`: carrito de compras
- `favoritos.php`: productos favoritos
- `ventas.php`: listado visual de ventas
- `usuarios.php`: listado visual de usuarios
- `categorias.php`: categorias
- `perfil.php`: perfil de usuario
- `logout.php`: cierre de sesion visual

## Estado actual

Incluye interfaz responsive con Bootstrap, estructura de carpetas, includes reutilizables, estilos propios, JavaScript basico e imagenes locales de muestra.

Tambien incluye:

- Login con sesiones.
- Proteccion de rutas por rol.
- Registro con `password_hash()`.
- Login con `password_verify()`.
- 2FA opcional por usuario.
- CRUD de productos con upload de imagenes.
- CRUD de categorias.
- Carrito en `$_SESSION`.
- Registro de ventas y detalle de venta.
- Descuento de stock al comprar.
- Favoritos por usuario.
- Dashboard con contadores reales, ultimas ventas y grafico de bajo stock.
- Filtros de productos en la landing.

## Base de datos

El proyecto usa PDO y la configuracion esta en `config/database.php`.

Configuracion por defecto para XAMPP:

- Host: `localhost`
- Base de datos: `tienda_gamer`
- Usuario: `root`
- Contrasena: vacia

Importa el archivo `database/tienda_gamer.sql` desde phpMyAdmin para crear las tablas y datos de ejemplo.

Si ya habias importado la base antes de agregar el 2FA avanzado, ejecuta tambien `database/migrations_2fa.sql`.

Para configurar credenciales locales, copia `.env.example` como `.env` y ajusta DB/SMTP. No subas `.env` a Git.

## Proximas implementaciones

- Paginacion de productos
- Recuperar contrasena
- Buscador AJAX
- Reportes PDF o Excel
- Mejoras visuales con SweetAlert o Toasts
