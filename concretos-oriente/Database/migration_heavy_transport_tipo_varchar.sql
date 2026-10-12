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
