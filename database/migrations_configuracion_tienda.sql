USE tienda_gamer;

CREATE TABLE IF NOT EXISTS configuracion_tienda (
    id_configuracion INT PRIMARY KEY DEFAULT 1,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(180) NOT NULL,
    horario VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(50) NOT NULL
);

INSERT INTO configuracion_tienda (id_configuracion, nombre, descripcion, ubicacion, horario, correo, telefono) VALUES
(1, 'GameZone Store', 'Tienda especializada en hardware, perifericos y equipos gamer.', 'Av. Gamer 123, Zona Central, La Paz.', 'Lunes a sabado de 09:00 a 19:00.', 'ventas@gamezone.test', '+591 70000000')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    ubicacion = VALUES(ubicacion),
    horario = VALUES(horario),
    correo = VALUES(correo),
    telefono = VALUES(telefono);
