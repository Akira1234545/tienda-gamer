# GameZone Store

Sistema web de venta de equipos gamer desarrollado con PHP, MySQL, Bootstrap 5, JavaScript y Composer.

## Modulos

- Landing dinamica con productos, categorias, buscador y filtros.
- Login, registro, logout y sesiones.
- Roles `admin` y `cliente`.
- 2FA por correo con PHPMailer.
- CRUD de productos con upload de imagenes.
- CRUD de categorias.
- Carrito con `$_SESSION`.
- Checkout con transacciones PDO.
- Ventas y detalle de venta.
- Descuento automatico de stock.
- Favoritos por usuario.
- Historial de compras del cliente.
- Dashboard administrativo con metricas, bajo stock y productos mas vendidos.
- Panel de usuarios con cambio de roles.
- Recibo post-compra con numero de venta y resumen.
- Informacion de tienda editable desde el panel admin.
- Busqueda y paginacion en tablas administrativas.

## Tecnologias

- PHP 8.2
- MySQL / MariaDB
- PDO
- Bootstrap 5
- JavaScript
- Composer
- PHPMailer
- Chart.js

## Instalacion

1. Copia el proyecto dentro de `C:\xampp\htdocs`.
2. Instala dependencias:

```bash
composer install
```

3. Importa la base de datos desde phpMyAdmin:

```text
database/tienda_gamer.sql
```

4. Si ya tenias la base importada antes del 2FA avanzado, ejecuta:

```text
database/migrations_2fa.sql
```

Si tu base fue creada antes de la seccion editable de tienda, ejecuta:

```text
database/migrations_configuracion_tienda.sql
```

5. Copia `.env.example` como `.env` y ajusta credenciales:

```text
DB_HOST=localhost
DB_NAME=tienda_gamer
DB_USER=root
DB_PASS=
```

6. Abre:

```text
http://localhost/Store_Gamming_Proyect/
```

## Usuario admin

```text
Correo: admin@tiendagamer.com
Contrasena: Admin123!
```

Si tu admin fue creado antes, actualiza el hash:

```sql
UPDATE usuario
SET contraseña = '$2y$10$VBMWwQMsCKU4FLX/LUGHF.SHKOxyUXJSWrd2bz2xYCL5H/hMMv8oy'
WHERE correo = 'admin@tiendagamer.com';
```

## SMTP 2FA

En `.env`:

```text
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=tu_password_de_aplicacion
SMTP_FROM=tu_correo@gmail.com
SMTP_FROM_NAME=GameZone Store
```

## Seguridad

- Consultas preparadas PDO.
- `password_hash()` y `password_verify()`.
- `htmlspecialchars()` para salida.
- Tokens CSRF en formularios.
- `session_regenerate_id()` al iniciar sesion.
- Expiracion de sesion por inactividad.
- Validacion de uploads por MIME, tamano y extension.
- `.env` ignorado por Git.

## Git recomendado

```bash
git add .
git commit -m "feat: sistema tienda gamer funcional"
git remote add origin URL_DEL_REPOSITORIO
git push -u origin main
```

No se sube `vendor/`; se restaura con `composer install`.
