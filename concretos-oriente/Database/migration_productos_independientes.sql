-- Migración: Campos de productos independientes en concrete_trips
-- Ejecutar en la base de datos del servidor

ALTER TABLE `concrete_trips`
  ADD COLUMN `m3_arena` decimal(10,2) DEFAULT NULL AFTER `m3`,
  ADD COLUMN `m3_piedrin` decimal(10,2) DEFAULT NULL AFTER `m3_arena`,
  ADD COLUMN `m3_cemento` decimal(10,2) DEFAULT NULL AFTER `m3_piedrin`;

-- Una vez confirmado que los datos anteriores ya no son necesarios:
ALTER TABLE `concrete_trips`
  DROP COLUMN `producto`,
  DROP COLUMN `m3`;
