-- 002: `equipos` y `jugadores` conservan sus datos reales; se les agregan los campos opcionales
-- que usa el diseño Pulse y `equipos` deja de guardar el login (ya está en `usuarios`).

-- Seguridad: no continuar si el login no fue copiado completo a `usuarios`.
SET @sin_copiar = (SELECT COUNT(*) FROM equipos e WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.usuario = e.usuario));
SET @sql = IF(@sin_copiar > 0, 'SELECT * FROM __migracion_001_incompleta__', 'SELECT 1');
PREPARE chequeo FROM @sql; EXECUTE chequeo; DEALLOCATE PREPARE chequeo;

-- La fila "Administración" no es un equipo.
DELETE FROM equipos WHERE rol = 'admin' AND NOT EXISTS (SELECT 1 FROM jugadores j WHERE j.equipo_id = equipos.id);

ALTER TABLE equipos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE jugadores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE equipos
    DROP INDEX usuario,
    DROP COLUMN usuario,
    DROP COLUMN password_hash,
    DROP COLUMN rol,
    ADD COLUMN abreviatura VARCHAR(4) NULL AFTER nombre,
    ADD COLUMN ciudad VARCHAR(100) NULL,
    ADD COLUMN estadio VARCHAR(100) NULL,
    ADD COLUMN entrenador VARCHAR(100) NULL,
    ADD COLUMN color_principal CHAR(7) NOT NULL DEFAULT '#c6ff3b',
    ADD COLUMN color_secundario CHAR(7) NOT NULL DEFAULT '#101518',
    ADD COLUMN anio_fundacion SMALLINT NULL;

ALTER TABLE jugadores
    ADD COLUMN dorsal TINYINT UNSIGNED NULL AFTER nombre,
    ADD COLUMN fecha_nacimiento DATE NULL,
    ADD COLUMN nacionalidad VARCHAR(60) NOT NULL DEFAULT 'Guatemala',
    ADD COLUMN estatura_cm SMALLINT UNSIGNED NULL,
    ADD COLUMN peso_kg DECIMAL(5,2) NULL,
    ADD COLUMN pie_habil ENUM('Derecho','Izquierdo','Ambidiestro') NULL,
    ADD UNIQUE KEY uq_jugadores_equipo_dorsal (equipo_id, dorsal);
