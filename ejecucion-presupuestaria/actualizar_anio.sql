-- =====================================================
-- ACTUALIZACIÓN: Agregar campo 'anio' a las tablas
-- Sistema de Ejecución Presupuestaria - MAGA
-- =====================================================

USE ejecucion_presupuestaria;

-- Agregar campo anio a ejecucion_principal
ALTER TABLE ejecucion_principal 
ADD COLUMN anio INT NOT NULL DEFAULT 2025 AFTER tipo_ejecucion_id,
ADD INDEX idx_ep_anio (anio);

-- Agregar campo anio a ejecucion_detalle
ALTER TABLE ejecucion_detalle 
ADD COLUMN anio INT NOT NULL DEFAULT 2025 AFTER tipo_registro,
ADD INDEX idx_ed_anio (anio);

-- Agregar campo anio a ejecucion_ministerios
ALTER TABLE ejecucion_ministerios 
ADD COLUMN anio INT NOT NULL DEFAULT 2025 AFTER ministerio_id,
ADD INDEX idx_em_anio (anio);

-- Actualizar las vistas para incluir el campo anio

-- Vista de Ejecución Principal con nombres
DROP VIEW IF EXISTS v_ejecucion_principal;
CREATE VIEW v_ejecucion_principal AS
SELECT 
    ep.id,
    ep.anio,
    ue.codigo as unidad_codigo,
    ue.nombre as unidad_nombre,
    ue.nombre_corto as unidad_corto,
    p.codigo as programa_codigo,
    p.nombre as programa_nombre,
    gg.codigo as grupo_gasto_codigo,
    gg.nombre as grupo_gasto_nombre,
    ff.codigo as fuente_codigo,
    ff.nombre as fuente_nombre,
    te.nombre as tipo_ejecucion,
    ep.asignado,
    ep.modificado,
    ep.vigente,
    ep.devengado,
    ep.saldo_por_devengar,
    ep.porcentaje_ejecucion,
    ep.porcentaje_relativo,
    ep.porcentaje_ejecucion_al_dia,
    ep.fecha_registro,
    COALESCE(
        CONCAT(ue.codigo, ' "', ue.nombre_corto, '"'),
        CONCAT(p.codigo, ' "', p.nombre, '"'),
        CONCAT(gg.codigo, ' "', gg.nombre, '"'),
        CONCAT(ff.codigo, ' "', ff.nombre, '"')
    ) as progra_uni_gasto_finan
FROM ejecucion_principal ep
LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
LEFT JOIN programas p ON ep.programa_id = p.id
LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
LEFT JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id;

-- Vista de Detalle con nombres
DROP VIEW IF EXISTS v_ejecucion_detalle;
CREATE VIEW v_ejecucion_detalle AS
SELECT 
    ed.id,
    ed.anio,
    ue.codigo as unidad_codigo,
    ue.nombre as unidad_nombre,
    ue.nombre_corto as unidad_corto,
    gg.codigo as grupo_gasto_codigo,
    gg.nombre as grupo_gasto_nombre,
    ff.codigo as fuente_codigo,
    ff.nombre as fuente_nombre,
    ed.tipo_registro,
    ed.vigente,
    ed.devengado,
    ed.saldo_por_devengar,
    ed.porcentaje_ejecucion,
    ed.porcentaje_relativo,
    ed.fecha_registro,
    COALESCE(
        CONCAT(gg.codigo, ' "', gg.nombre, '"'),
        CONCAT(ff.codigo, ' "', ff.nombre, '"')
    ) as tipo_gasto_financiamiento
FROM ejecucion_detalle ed
LEFT JOIN unidades_ejecutoras ue ON ed.unidad_ejecutora_id = ue.id
LEFT JOIN grupos_gasto gg ON ed.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ed.fuente_financiamiento_id = ff.id;

-- Vista de Ministerios
DROP VIEW IF EXISTS v_ejecucion_ministerios;
CREATE VIEW v_ejecucion_ministerios AS
SELECT 
    em.id,
    em.anio,
    m.nombre as ministerio,
    m.siglas,
    em.asignado,
    em.modificado,
    em.vigente,
    em.devengado,
    em.saldo_por_devengar,
    em.porcentaje_ejecucion,
    em.porcentaje_relativo,
    em.fecha_registro
FROM ejecucion_ministerios em
JOIN ministerios m ON em.ministerio_id = m.id;

-- =====================================================
-- FIN ACTUALIZACIÓN
-- =====================================================
