-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Horarios de estudio (Asistente WhatsApp)
-- Cada usuario configura, por WhatsApp, a qué curso, qué días, a qué
-- hora y cuántos minutos quiere estudiar. El Asistente usa esto para
-- mandar el recordatorio 10 minutos antes de la hora configurada.
-- Un registro por (usuario, curso) — varios cursos = varias filas.
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001, 002 y 003.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS horarios_curso (
    id                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id                 INT UNSIGNED NOT NULL,
    curso_id                   INT UNSIGNED NOT NULL,
    dias                       VARCHAR(20) NOT NULL,           -- ej. "L,M,X,V" (L=lunes M=martes X=miércoles J=jueves V=viernes S=sábado D=domingo)
    hora                       TIME NOT NULL,                  -- hora de estudio configurada
    duracion_minutos           SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    activo                     TINYINT(1) NOT NULL DEFAULT 1,  -- "pausar avisos" lo pone en 0
    pospuesto_hasta            DATETIME NULL,                  -- "más tarde": próximo aviso puntual, no cambia "hora"
    ultima_notificacion_fecha  DATE NULL,                      -- evita mandar el recordatorio dos veces el mismo día
    created_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_horarios_usuario_curso (usuario_id, curso_id),
    KEY idx_horarios_hora (hora),
    CONSTRAINT fk_horarios_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_horarios_curso
        FOREIGN KEY (curso_id) REFERENCES cursos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
