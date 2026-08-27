-- =====================================================================
-- Migración: contorno (polígono) de la parcela dibujado sobre mapa satelital.
-- Ejecutar UNA sola vez sobre la base de datos visionwe_Keyline ya existente.
--
-- Guarda el contorno como JSON: un arreglo de pares [lat, lng].
-- Ej: [[15.47,-90.37],[15.48,-90.36],[15.46,-90.35]]
-- NULL = la parcela no tiene contorno dibujado.
-- =====================================================================

ALTER TABLE `parcelas`
  ADD COLUMN `poligono` TEXT DEFAULT NULL
      COMMENT 'Contorno de la parcela: JSON [[lat,lng], ...]'
      AFTER `gps_precision`;

-- =====================================================================
-- Verifica el resultado:
--   DESCRIBE `parcelas`;   -- debe aparecer la columna `poligono`
-- =====================================================================
