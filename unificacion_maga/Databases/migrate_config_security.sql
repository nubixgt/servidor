-- ============================================================
--  Migración: Seguridad y Configuración Global del Sistema
--  Fecha: 2026-06-22  (actualizado: 2026-06-23)
--  NOTA: El nombre real de la columna en maga_usuarios es
--        `created_at`, no `creado_at`.
-- ============================================================

-- 1. Columnas adicionales en maga_usuarios
--    (rol, permisos, puesto, ubicacion, seguridad por fuerza bruta)
ALTER TABLE `maga_usuarios`
    ADD COLUMN IF NOT EXISTS `puesto_funcional`   VARCHAR(150) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ubicacion_laboral`  VARCHAR(150) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `permisos`           TEXT         NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `intentos_fallidos`  INT          NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `bloqueado_hasta`    DATETIME     NULL DEFAULT NULL;

-- Normalizar el valor del rol 'admin' → 'ADMIN' (consistencia con el frontend)
UPDATE `maga_usuarios` SET `rol` = 'ADMIN'   WHERE LOWER(`rol`) IN ('admin', 'administrador');
UPDATE `maga_usuarios` SET `rol` = 'TECNICO' WHERE LOWER(`rol`) IN ('tecnico', 'técnico');

-- 2. Tabla de configuraciones globales del sistema
CREATE TABLE IF NOT EXISTS `maga_settings` (
    `key`         VARCHAR(50)  NOT NULL,
    `value`       TEXT         NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Valores por defecto de configuración
INSERT IGNORE INTO `maga_settings` (`key`, `value`, `description`) VALUES
    ('session_timeout',    '480',              'Tiempo máximo de sesión en minutos (480 = 8 horas)'),
    ('maintenance_mode',   'false',            'Activar modo mantenimiento del sistema (true/false)'),
    ('openweather_api_key','YOUR_API_KEY_HERE','Llave de API para el módulo climatológico de OpenWeather');
