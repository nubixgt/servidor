-- Ejecuta esto en phpMyAdmin u otra herramienta sobre la BD u991565456_maga_un

ALTER TABLE `maga_usuarios` 
ADD COLUMN IF NOT EXISTS `puesto_funcional` VARCHAR(150) NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `ubicacion_laboral` VARCHAR(150) NULL AFTER `puesto_funcional`,
ADD COLUMN IF NOT EXISTS `permisos` JSON NULL AFTER `rol`,
ADD COLUMN IF NOT EXISTS `ultimo_acceso` TIMESTAMP NULL AFTER `activo`;
