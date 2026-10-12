-- ============================================================================
-- Migración: heavy_transport.tipo_transporte pasa de ENUM a VARCHAR
-- ----------------------------------------------------------------------------
-- Antes: ENUM('Volteo','Pipa','Trailer Concreto'), pero el formulario de
--        Transporte Pesado envía 'Camion de volteo', 'Rastra', 'Gondola',
--        'Lowboy', 'Cabezal', 'Camion 5 toneladas', 'Camion 10 toneladas'.
--        MySQL rechaza esos valores con "1265 Data truncated for column
--        'tipo_transporte'" y no se puede registrar la unidad.
--
-- Este cambio NO borra ni altera datos: MySQL convierte cada valor ENUM
-- existente a su texto equivalente en el VARCHAR.
-- ============================================================================

ALTER TABLE `heavy_transport`
  MODIFY `tipo_transporte` VARCHAR(50) NOT NULL;

-- ----------------------------------------------------------------------------
-- Mismo problema en `estado` y `tipo_seguro`:
--   estado      ENUM('Nuevo','En Funcionamiento','Inactivo') pero el formulario
--               y el backend usan 'Activo' / 'Inactivo' (commit bafadb45).
--   tipo_seguro ENUM('Full Cover','Danos a Terceros') pero el formulario
--               también ofrece 'Responsabilidad Civil'.
-- ----------------------------------------------------------------------------

ALTER TABLE `heavy_transport`
  MODIFY `estado` VARCHAR(30) NOT NULL DEFAULT 'Activo',
  MODIFY `tipo_seguro` VARCHAR(50) NULL DEFAULT NULL;
