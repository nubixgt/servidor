-- ============================================================
-- Script de limpieza de tablas obsoletas
-- Base de datos: u991565456_maga_un
-- Fecha: 15 de junio 2026
-- ============================================================

-- ------------------------------------------------------------
-- GRUPO A: Backups obsoletos (creados durante migración de prefijos)
-- Seguro eliminar: sus datos ya viven en las tablas activas con prefijo correcto
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `tobanik_old`;           -- Backup de vider_tobanik (ya migrado)
DROP TABLE IF EXISTS `congresistas_backup`;   -- Backup vacío de votaciones_congresistas
DROP TABLE IF EXISTS `votos_backup`;          -- Backup vacío de votaciones_votos

-- ------------------------------------------------------------
-- GRUPO B: Vistas/tablas duplicadas sin uso en el backend
-- Tienen equivalentes activos con nombres correctos
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `vista_detalle_eventos`;              -- Duplicado de votaciones_vista_detalle_eventos
DROP TABLE IF EXISTS `vista_estadisticas_bloque`;          -- Duplicado de votaciones_vista_estadisticas_bloque
DROP TABLE IF EXISTS `votaciones_historial_bloques`;        -- Sin uso en código (info en votaciones_votos)
DROP TABLE IF EXISTS `votaciones_vista_detalle_eventos`;   -- Sin referencia activa en backend
DROP TABLE IF EXISTS `votaciones_vista_estadisticas_bloque`; -- Sin referencia activa en backend

-- ============================================================
-- TOTAL: 8 tablas eliminadas
-- ============================================================
