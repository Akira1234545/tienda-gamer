-- Base de datos: Sistema Web de Venta de Equipos Gamer

CREATE DATABASE IF NOT EXISTS tienda_gamer
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tienda_gamer;

CREATE TABLE IF NOT EXISTS categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    `contraseña` VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    codigo_2fa VARCHAR(10) DEFAULT NULL,
    codigo_2fa_expira DATETIME DEFAULT NULL,
    intentos_2fa INT NOT NULL DEFAULT 0,
    estado_2fa TINYINT(1) NOT NULL DEFAULT 0,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria)
        REFERENCES categoria(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    estado_venta ENUM('Pendiente', 'Pagado', 'Entregado') NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT fk_venta_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_venta FOREIGN KEY (id_venta)
        REFERENCES venta(id_venta)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS favorito (
    id_favorito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_favorito_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_favorito_producto FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS configuracion_tienda (
    id_configuracion INT PRIMARY KEY DEFAULT 1,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(180) NOT NULL,
    horario VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(50) NOT NULL
);

INSERT INTO categoria (nombre_categoria, descripcion) VALUES
('Laptops Gamer', 'Laptops de alto rendimiento para gaming'),
('Monitores', 'Monitores gaming con alta tasa de refresco'),
('Mouse', 'Ratones gaming de precision'),
('Teclados', 'Teclados mecanicos y membrana para gaming'),
('Consolas', 'Consolas de videojuegos de ultima generacion');

-- Admin por defecto. Contrasena: Admin123!
INSERT INTO usuario (nombre, correo, `contraseña`, rol) VALUES
('Administrador', 'admin@tiendagamer.com',
 '$2y$10$VBMWwQMsCKU4FLX/LUGHF.SHKOxyUXJSWrd2bz2xYCL5H/hMMv8oy', 'admin');

INSERT INTO producto (id_categoria, nombre, marca, descripcion, precio, stock, imagen) VALUES
(1, 'Laptop Gamer ASUS', 'ASUS', 'RTX 4060 - Ryzen 7 - 16GB RAM', 1200.00, 10, 'producto1.jpg'),
(4, 'Teclado Mecanico RGB', 'Redragon', 'Switch Blue - RGB completo', 90.00, 25, 'producto2.jpg'),
(2, 'Monitor 240Hz', 'LG', 'Full HD - 1ms - Gamer Pro', 450.00, 8, 'producto3.jpg');

INSERT INTO configuracion_tienda (id_configuracion, nombre, descripcion, ubicacion, horario, correo, telefono) VALUES
(1, 'GameZone Store', 'Tienda especializada en hardware, perifericos y equipos gamer.', 'Av. Gamer 123, Zona Central, La Paz.', 'Lunes a sabado de 09:00 a 19:00.', 'ventas@gamezone.test', '+591 70000000')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    ubicacion = VALUES(ubicacion),
    horario = VALUES(horario),
    correo = VALUES(correo),
    telefono = VALUES(telefono);
