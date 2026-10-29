-- ============================================================================
-- Migración: vehicle_logs.estatus_vehiculo pasa de ENUM a VARCHAR
-- ----------------------------------------------------------------------------
-- Complemento de migration_vehicles_special_machinery_varchar.sql: la bitácora
-- diaria envía 'Activo', que el ENUM ('Nuevo','En Funcionamiento','Inactivo')
-- no acepta, y MySQL rechaza el registro con "1265 Data truncated".
--
-- No borra ni altera datos: cada valor ENUM existente se conserva como texto.
-- ============================================================================

ALTER TABLE `vehicle_logs`
  MODIFY `estatus_vehiculo` VARCHAR(30) NOT NULL DEFAULT 'Activo';
