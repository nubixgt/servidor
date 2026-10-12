-- ============================================================================
-- Migración: columnas ENUM de vehicles y special_machinery pasan a VARCHAR
-- ----------------------------------------------------------------------------
-- Igual que en heavy_transport: los formularios envían valores que los ENUM
-- no aceptan y MySQL rechaza el registro con "1265 Data truncated":
--   vehicles.tipo_vehiculo          -> 'Camioncito menor a 5T'
--   vehicles.estatus                -> 'Activo' (commit bafadb45)
--   special_machinery.tipo_maquinaria -> 'Bomba de concreto', 'Dosificadora de concreto', 'Otra'
--   special_machinery.estado        -> 'Activo' (commit bafadb45)
--
-- No borra ni altera datos: cada valor ENUM existente se conserva como texto.
-- ============================================================================

ALTER TABLE `vehicles`
  MODIFY `tipo_vehiculo` VARCHAR(50) NOT NULL DEFAULT 'Sedan',
  MODIFY `tipo_seguro` VARCHAR(50) NULL DEFAULT NULL,
  MODIFY `estatus` VARCHAR(30) NOT NULL DEFAULT 'Activo';

ALTER TABLE `special_machinery`
  MODIFY `tipo_maquinaria` VARCHAR(50) NOT NULL,
  MODIFY `estado` VARCHAR(30) NOT NULL DEFAULT 'Activo';
