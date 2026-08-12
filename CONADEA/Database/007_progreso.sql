-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Progreso del usuario
-- Hasta ahora el avance (lecciones completadas, nota, aprobado) vivía solo
-- en memoria dentro de la app (ProgresoController), así que no sobrevivía
-- a cerrar la app ni servía entre dispositivos. Estas tablas lo persisten
-- por usuario, y de paso permiten guardar en qué segundo del video se
-- quedó cada lección para retomar la reproducción donde la dejó.
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001, 002, 003, 005 y 006.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- progreso_lecciones
-- Un registro por (usuario, lección): si la marcó completada y en qué
-- segundo del video se quedó (0 si no ha visto video o no aplica).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso_lecciones (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id     INT UNSIGNED NOT NULL,
    leccion_id     INT UNSIGNED NOT NULL,
    completada     TINYINT(1)   NOT NULL DEFAULT 0,
    segundos_video INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progreso_leccion (usuario_id, leccion_id),
    KEY idx_progreso_lecciones_leccion (leccion_id),
    CONSTRAINT fk_progreso_leccion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_progreso_leccion_leccion
        FOREIGN KEY (leccion_id) REFERENCES lecciones (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- progreso_cursos
-- Un registro por (usuario, curso): resultado de la evaluación final.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso_cursos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id     INT UNSIGNED NOT NULL,
    curso_id       INT UNSIGNED NOT NULL,
    nota           INT UNSIGNED NULL,
    aprobado       TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_aprobado TIMESTAMP    NULL,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progreso_curso (usuario_id, curso_id),
    KEY idx_progreso_cursos_curso (curso_id),
    CONSTRAINT fk_progreso_curso_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_progreso_curso_curso
        FOREIGN KEY (curso_id) REFERENCES cursos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
