-- =====================================================================
--  TestBases · 02 · CATÁLOGOS Y USUARIOS INICIALES
--  Ejecutar DESPUÉS de 01_esquema.sql. Solo INSERT (sin TRUNCATE ni DROP).
--
--  Roles
--    admin    Ve toda la base, agrega y edita comentarios e imágenes y
--             puede ELIMINAR la imagen adjunta de una fila.
--    tecnico  Ve toda la base, agrega y edita comentarios e imágenes.
--             No puede eliminar imágenes.
--
--  Usuarios iniciales: admin y tecnico. Las contraseñas se entregaron por
--  separado y aquí solo existe su hash bcrypt. CÁMBIALAS al primer ingreso:
--    php -r "echo password_hash('NUEVA_CLAVE', PASSWORD_BCRYPT), PHP_EOL;"
--    UPDATE usuarios SET clave_hash = '<hash>' WHERE usuario = 'admin';
-- =====================================================================

USE testbases;
SET NAMES utf8mb4;

INSERT INTO roles (id, codigo, nombre, descripcion) VALUES
    (1, 'admin',   'Administrador', 'Acceso total, incluida la eliminación de imágenes adjuntas'),
    (2, 'tecnico', 'Técnico',       'Consulta la base y registra comentarios e imágenes por fila');

INSERT INTO usuarios (rol_id, usuario, clave_hash, nombre_completo) VALUES
    (1, 'admin',   '$2b$11$srmUrX1sc8zRSCVXcCWWE.fGqkAHm7SSGVhiMKBvQzxdF.G3AkOl.', 'Administrador'),
    (2, 'tecnico', '$2b$11$1aT5DUnQ3lUrbst5bzDjne9clc8tqciaatiS1mUhQEflme0Rz2lq6', 'Técnico');
