-- 001: separa el inicio de sesión de `equipos` en la tabla `usuarios`.
-- Cada fila actual de `equipos` (32 encargados + 1 admin) pasa a `usuarios` con su mismo hash,
-- así que todas las contraseñas siguen funcionando.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','encargado') NOT NULL DEFAULT 'encargado',
    equipo_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL,
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY idx_usuarios_equipo (equipo_id),
    CONSTRAINT fk_usuarios_equipo FOREIGN KEY (equipo_id) REFERENCES equipos (id) ON DELETE CASCADE,
    -- el encargado siempre pertenece a un equipo; el admin a ninguno
    CONSTRAINT chk_usuarios_rol_equipo CHECK ((rol = 'admin' AND equipo_id IS NULL) OR (rol = 'encargado' AND equipo_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO usuarios (usuario, password_hash, rol, equipo_id, fecha_creacion)
SELECT usuario, password_hash, COALESCE(rol, 'encargado'), IF(COALESCE(rol, 'encargado') = 'admin', NULL, id), fecha_creacion
FROM equipos;
