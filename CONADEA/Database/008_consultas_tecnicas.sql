-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Consultas técnicas (Asistente WhatsApp)
-- Guarda las fotos/audios/ubicaciones que un usuario envía por el
-- Asistente de WhatsApp (opción "Consulta técnica") para que un técnico
-- las revise después. No hay análisis de IA en esta versión: es solo
-- una bandeja de entrada.
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001, 002, 003, 005, 006 y 007.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS consultas_tecnicas (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id   INT UNSIGNED NOT NULL,
    tipo         ENUM('imagen', 'audio', 'ubicacion', 'texto') NOT NULL,
    contenido    VARCHAR(500) NULL,   -- ruta relativa del archivo (uploads/consultas/...), o "lat,lng"
    mensaje      TEXT         NULL,   -- caption o texto libre opcional del usuario
    estado       ENUM('pendiente', 'atendida') NOT NULL DEFAULT 'pendiente',
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consultas_usuario (usuario_id),
    KEY idx_consultas_estado (estado),
    CONSTRAINT fk_consultas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
