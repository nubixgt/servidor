-- =====================================================================
-- Migración: menús normalizados en el formulario de Registrar Parcela.
-- Ejecutar UNA sola vez sobre la base de datos visionwe_Keyline ya existente.
--
-- Cambios:
--   * tipo_suelo            -> clase_textural          (renombre)
--   * fuente_agua           -> fuente_agua_principal   (renombre)
--   * + fuente_agua_secundaria (texto, separada por coma, multi-selección)
--   * + limitantes_uso         (texto, separada por coma, multi-selección)
--   * - agua      (Disponibilidad de agua: la revisión pidió eliminarlo)
--   * - talpetate (reemplazado por "Limitante de uso")
-- =====================================================================

-- 1) Renombrar columnas y agregar las nuevas
ALTER TABLE `parcelas`
  CHANGE COLUMN `tipo_suelo` `clase_textural` VARCHAR(150) DEFAULT NULL
        COMMENT 'Clase textural del suelo (USDA) o valor libre',
  CHANGE COLUMN `fuente_agua` `fuente_agua_principal` VARCHAR(150) DEFAULT NULL
        COMMENT 'Fuente original de agua (principal)',
  ADD COLUMN `fuente_agua_secundaria` TEXT DEFAULT NULL
        COMMENT 'Fuentes secundarias de agua, separadas por coma'
        AFTER `fuente_agua_principal`,
  ADD COLUMN `limitantes_uso` TEXT DEFAULT NULL
        COMMENT 'Limitantes de uso de la parcela, separadas por coma'
        AFTER `encharca`;

-- 2) Conservar el dato existente de talpetate como una limitante de uso
UPDATE `parcelas`
   SET `limitantes_uso` = 'Talpetate o capa endurecida'
 WHERE `talpetate` = 'Sí';

-- 3) (Opcional) Conservar la disponibilidad de agua previa dentro de observaciones,
--    antes de eliminar la columna. Descomenta si te interesa no perder ese dato.
-- UPDATE `parcelas`
--    SET `observaciones` = TRIM(CONCAT(COALESCE(`observaciones`, ''),
--        '\n[Disponibilidad de agua previa: ', `agua`, ']'))
--  WHERE `agua` IS NOT NULL AND `agua` <> '';

-- 4) Eliminar las columnas que ya no se usan
ALTER TABLE `parcelas`
  DROP COLUMN `agua`,
  DROP COLUMN `talpetate`;

-- =====================================================================
-- Verifica el resultado:
--   DESCRIBE `parcelas`;
-- Deben aparecer: clase_textural, fuente_agua_principal, fuente_agua_secundaria,
-- limitantes_uso. Y ya NO deben aparecer: agua, talpetate.
-- =====================================================================
