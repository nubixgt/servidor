-- Migración: Agregar depto_nacimiento, muni_nacimiento y estado_civil a la tabla personnel
-- Ejecutar en la base de datos MySQL (phpMyAdmin o consola)

ALTER TABLE `personnel`
  ADD COLUMN `depto_nacimiento` VARCHAR(100) DEFAULT NULL AFTER `fecha_nacimiento`,
  ADD COLUMN `muni_nacimiento` VARCHAR(100) DEFAULT NULL AFTER `depto_nacimiento`,
  ADD COLUMN `estado_civil` VARCHAR(50) DEFAULT NULL AFTER `muni_nacimiento`;
