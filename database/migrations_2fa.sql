USE tienda_gamer;

ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS codigo_2fa_expira DATETIME DEFAULT NULL AFTER codigo_2fa,
    ADD COLUMN IF NOT EXISTS intentos_2fa INT NOT NULL DEFAULT 0 AFTER codigo_2fa_expira;
