-- =====================================================================
-- Migración 003: elimina los puntos de muestreo (ya no existen en el formulario).
-- Ejecutar UNA sola vez, DESPUÉS de desplegar el código nuevo del backend.
-- ATENCIÓN: borra de forma definitiva los puntos de muestreo ya guardados.
-- =====================================================================

DROP TABLE IF EXISTS `danos_cultivos_puntos`;
