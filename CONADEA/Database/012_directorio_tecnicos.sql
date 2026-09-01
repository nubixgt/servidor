-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Directorio de Técnicos
-- Lista de técnicos de CONADEA (del "Directorio de Técnicos CONADEA 2026").
-- Sólo se usa en el bot de WhatsApp: cuando un número que está en esta
-- tabla escribe al Asistente y todavía NO tiene cuenta registrada en
-- `usuarios`, se le responde un mensaje de bienvenida personalizado con su
-- nombre en vez del genérico. Al crear su cuenta, `usuarios` manda y esta
-- tabla deja de consultarse para ese número.
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001.
-- Script re-ejecutable (ON DUPLICATE KEY UPDATE).
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS directorio_tecnicos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150) NOT NULL,
    telefono    VARCHAR(20)  NOT NULL,          -- normalizado: 502 + 8 dígitos
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_directorio_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO directorio_tecnicos (nombre, telefono) VALUES
    ('Daan García',           '50251230714'),
    ('Francisco Díaz',        '50240984303'),
    ('Jhon Carlos Marroquín', '50239229859'),
    ('José David Ochaeta',    '50259352925'),
    ('Allan Villavicencio',   '50231672514'),
    ('Elmer Vidal',           '50230302378'),
    ('Hector Carrillo',       '50258738995'),
    ('Herbert Arzú',          '50238074513'),
    ('José Miguel Solís',     '50255513330'),
    ('Miguel de León',        '50256316961')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;
