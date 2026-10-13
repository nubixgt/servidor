-- 003: `partidos` y `estadisticas_partido` estaban vacías: se reemplazan por el modelo nuevo.
-- El marcador ya no se guarda: se calcula desde `eventos_partido` (una sola fuente de verdad).

-- Seguridad: solo se recrean si realmente están vacías.
SET @filas = (SELECT COUNT(*) FROM partidos) + (SELECT COUNT(*) FROM estadisticas_partido);
SET @sql = IF(@filas > 0, 'SELECT * FROM __partidos_tiene_datos_no_se_recrea__', 'SELECT 1');
PREPARE chequeo FROM @sql; EXECUTE chequeo; DEALLOCATE PREPARE chequeo;

DROP TABLE IF EXISTS estadisticas_partido;
DROP TABLE IF EXISTS partidos;

CREATE TABLE partidos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL COMMENT 'UTC',
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    jornada TINYINT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('programado','en_juego','finalizado') NOT NULL DEFAULT 'programado',
    registrado_por INT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_partidos_local (equipo_local_id),
    KEY idx_partidos_visitante (equipo_visitante_id),
    CONSTRAINT fk_partidos_local FOREIGN KEY (equipo_local_id) REFERENCES equipos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_partidos_visitante FOREIGN KEY (equipo_visitante_id) REFERENCES equipos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_partidos_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT chk_partidos_distintos CHECK (equipo_local_id <> equipo_visitante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos_partido (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    jugador_id INT NOT NULL,
    asistente_id INT NULL,
    tipo ENUM('gol','autogol','amarilla','roja') NOT NULL,
    minuto TINYINT UNSIGNED NOT NULL,
    KEY idx_eventos_partido (partido_id),
    KEY idx_eventos_jugador (jugador_id),
    KEY idx_eventos_asistente (asistente_id),
    CONSTRAINT fk_eventos_partido FOREIGN KEY (partido_id) REFERENCES partidos (id) ON DELETE CASCADE,
    CONSTRAINT fk_eventos_equipo FOREIGN KEY (equipo_id) REFERENCES equipos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_eventos_jugador FOREIGN KEY (jugador_id) REFERENCES jugadores (id) ON DELETE RESTRICT,
    CONSTRAINT fk_eventos_asistente FOREIGN KEY (asistente_id) REFERENCES jugadores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
